<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Internamento extends Model
{
    use HasFactory;

    /**
     * Causas selecionáveis para um reinternamento identificado como
     * readmissão (<30 dias após uma alta anterior do mesmo doente). Guardadas
     * em `causas_readmissao` como array json — um internamento pode ter mais
     * do que uma causa associada.
     */
    public const CAUSAS_READMISSAO_OPTIONS = [
        'complicacao_cirurgica',
        'infecao_ferida_operatoria',
        'complicacao_medica',
        'recidiva_agravamento',
        'efeito_adverso_medicacao',
        'problema_gestao_alta',
        'nao_relacionado',
        'outra',
    ];

    protected $fillable = ['asa', 'bloquear', 'causas_readmissao', 'clavien_dindo_id', 'consulta_pos_alta_marcada', 'contacto_telefonico_pos_alta', 'data_admissao_urgencia', 'data_alta', 'data_consulta_pos_alta', 'data_contacto_pos_alta', 'data_entrada', 'data_prevista_alta', 'data_saida', 'destino_detalhe_id', 'destino_id', 'dias_cuidados_intensivos', 'dias_cuidados_intermedios', 'dias_internamento', 'episodio', 'episodio_urgencia', 'equipa_id', 'falecido', 'falecido_apos_alta', 'hora_alta', 'indice_charlson', 'mortalidade_esperada', 'motivo_nao_antecipacao_alta_id', 'observacoes', 'origem_id', 'patient_id', 'readmissao_planeada', 'readmissao_relacionada', 'responsavel_id', 'servico_destino'];

    protected $casts = [
        'causas_readmissao' => 'array',
        'consulta_pos_alta_marcada' => 'boolean',
        'contacto_telefonico_pos_alta' => 'boolean',
        'readmissao_planeada' => 'boolean',
        'readmissao_relacionada' => 'boolean',
    ];

    //

    public function clavienDindo()
    {
        return $this->belongsTo(ClavienDindo::class, 'clavien_dindo_id', 'id');
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id', 'id');
    }

    public function destinoDetalhe()
    {
        return $this->belongsTo(DestinoDetalhe::class, 'destino_detalhe_id', 'id');
    }

    public function motivoNaoAntecipacaoAlta()
    {
        return $this->belongsTo(MotivoNaoAntecipacaoAlta::class, 'motivo_nao_antecipacao_alta_id', 'id');
    }

    public function equipa()
    {
        return $this->belongsTo(Equipa::class, 'equipa_id', 'id');
    }

    public function origem()
    {
        return $this->belongsTo(Origem::class, 'origem_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id')->with('sexo');
    }

    public function blocoOperatorios()
    {
        return $this->hasMany(BlocoOperatorio::class, 'internamento_id', 'id')
            ->with('blocoOperatorioProcedimentos');
    }

    public function complicacaoInternamentos()
    {
        return $this->hasMany(ComplicacaoInternamento::class, 'internamento_id', 'id')->with('complicacao','resolucaos');
    }

    public function diagnosticoInternamentos()
    {
        return $this->hasMany(DiagnosticoInternamento::class, 'internamento_id', 'id')
            ->with('diagnostico');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id', 'id');
    }

    public function diagnosticos()
    {
        return $this->belongsToMany(Diagnostico::class)->withPivot('principal');
    }

    public function atrasosAlta()
    {
        return $this->hasMany(AtrasoAlta::class, 'internamento_id', 'id')->with('motivo');
    }

    /**
     * Filtra internamentos que são readmissões a 30 dias: existe, para o
     * mesmo doente, um internamento anterior com saída (`data_saida`) até
     * 30 dias antes desta entrada (`data_entrada`). Reaproveita a mesma
     * condição já usada em `InternamentoController::index` (coluna
     * `readmissao_30_dias`), agora como scope reutilizável nos indicadores.
     *
     * A diferença de dias é calculada de forma dependente do motor de BD
     * (`DATEDIFF` em MySQL, `julianday` em sqlite) porque a produção corre
     * em MySQL mas os testes correm em sqlite — sem isto o scope não seria
     * testável.
     */
    public function scopeReadmissaoA30Dias($query)
    {
        $driver = $query->getConnection()->getDriverName();

        $diffExpr = $driver === 'sqlite'
            ? 'julianday(internamentos.data_entrada) - julianday(i2.data_saida)'
            : 'DATEDIFF(internamentos.data_entrada, i2.data_saida)';

        return $query->whereExists(function ($sub) use ($diffExpr) {
            $sub->selectRaw('1')
                ->from('internamentos as i2')
                ->whereColumn('i2.patient_id', 'internamentos.patient_id')
                ->whereColumn('i2.id', '!=', 'internamentos.id')
                ->whereNotNull('i2.data_saida')
                ->whereColumn('i2.data_saida', '<=', 'internamentos.data_entrada')
                ->whereRaw("{$diffExpr} < 30");
        });
    }

    /**
     * O internamento anterior do mesmo doente que torna este internamento
     * uma readmissão a 30 dias (o mais recente que cumpre a condição), ou
     * null se este não for uma readmissão.
     */
    public function internamentoIndice(): ?self
    {
        if (!$this->patient_id || !$this->data_entrada) {
            return null;
        }

        return static::where('patient_id', $this->patient_id)
            ->where('id', '!=', $this->id)
            ->whereNotNull('data_saida')
            ->where('data_saida', '<=', $this->data_entrada)
            ->get()
            ->filter(fn (self $i) => Carbon::parse($i->data_saida)->diffInDays(Carbon::parse($this->data_entrada)) < 30)
            ->sortByDesc('data_saida')
            ->first();
    }

    /**
     * Equivalente à coluna "Demora Permanece Urg" do SONHO, calculada em vez
     * de guardada: dias entre a admissão na urgência e a entrada no
     * internamento (`data_entrada`).
     */
    protected function diasPermanenciaUrgencia(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->data_admissao_urgencia || !$this->data_entrada) {
                    return null;
                }

                return (int) Carbon::parse($this->data_admissao_urgencia)->diffInDays(Carbon::parse($this->data_entrada), false);
            },
        );
    }

    /**
     * Dias de atraso entre a decisão clínica de alta (`data_alta`) e a saída
     * efetiva (`data_saida`). Primeiro accessor do projeto (o padrão até
     * aqui era não ter nenhum) — necessário porque este valor é usado tanto
     * na UI (mostrar/escolher secção de atraso) como nos indicadores do
     * serviço, e não faz sentido duplicar o cálculo nos dois sítios.
     */
    protected function diasAtrasoAlta(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->data_alta || !$this->data_saida) {
                    return null;
                }

                $dias = (int) Carbon::parse($this->data_alta)->diffInDays(Carbon::parse($this->data_saida), false);

                return $dias > 0 ? $dias : null;
            },
        );
    }
}
