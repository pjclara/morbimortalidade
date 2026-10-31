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

    protected $fillable = ['bloquear', 'causas_readmissao', 'clavien_dindo_id', 'data_alta', 'data_entrada', 'data_saida', 'destino_id', 'dias_internamento', 'episodio', 'equipa_id', 'falecido', 'falecido_apos_alta', 'mortalidade_esperada', 'observacoes', 'origem_id', 'patient_id', 'responsavel_id'];

    protected $casts = [
        'causas_readmissao' => 'array',
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
