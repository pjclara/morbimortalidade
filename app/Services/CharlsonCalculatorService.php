<?php

namespace App\Services;

use App\Models\Internamento;
use Carbon\Carbon;

/**
 * Calcula o índice de comorbilidade de Charlson a partir dos diagnósticos
 * secundários (`diagnostico_internamento` com `principal=false`, tratados
 * como comorbilidades) e da idade do doente à entrada, usando os códigos
 * ICD-10-CM da adaptação de Quan (2005) e os pesos originais de Charlson
 * (1987).
 *
 * Por decisão de negócio, só calcula para internamentos com complicações
 * registadas (`complicacao_internamento`) — nos restantes casos devolve
 * null e o `indice_charlson` continua a ser o valor inserido manualmente.
 */
class CharlsonCalculatorService
{
    /**
     * Categoria => [peso, prefixos de código ICD-10-CM (sem ponto)].
     * Ordem sem significado; a exclusividade entre categorias sobrepostas
     * (diabetes, doença hepática, neoplasia) é tratada em `aplicarExclusividade`.
     */
    private const CATEGORIAS = [
        'enfarte_miocardio' => [1, ['I21', 'I22', 'I252']],
        'insuficiencia_cardiaca' => [1, ['I099', 'I110', 'I130', 'I132', 'I255', 'I420', 'I425', 'I426', 'I427', 'I428', 'I429', 'I43', 'I50', 'P290']],
        'doenca_vascular_periferica' => [1, ['I70', 'I71', 'I731', 'I738', 'I739', 'I771', 'I790', 'I792', 'K551', 'K558', 'K559', 'Z958', 'Z959']],
        'doenca_cerebrovascular' => [1, ['G45', 'G46', 'H340', 'I60', 'I61', 'I62', 'I63', 'I64', 'I65', 'I66', 'I67', 'I68', 'I69']],
        'demencia' => [1, ['F00', 'F01', 'F02', 'F03', 'F051', 'G30', 'G311']],
        'doenca_pulmonar_cronica' => [1, ['I278', 'I279', 'J40', 'J41', 'J42', 'J43', 'J44', 'J45', 'J46', 'J47', 'J60', 'J61', 'J62', 'J63', 'J64', 'J65', 'J66', 'J67', 'J684', 'J701', 'J703']],
        'doenca_reumatologica' => [1, ['M05', 'M06', 'M315', 'M32', 'M33', 'M34', 'M351', 'M353', 'M360']],
        'ulcera_peptica' => [1, ['K25', 'K26', 'K27', 'K28']],
        'doenca_hepatica_ligeira' => [1, ['B18', 'K700', 'K701', 'K702', 'K703', 'K709', 'K713', 'K714', 'K715', 'K717', 'K73', 'K74', 'K760', 'K762', 'K763', 'K764', 'K768', 'K769', 'Z944']],
        'diabetes_sem_complicacoes' => [1, ['E100', 'E101', 'E106', 'E108', 'E109', 'E110', 'E111', 'E116', 'E118', 'E119', 'E120', 'E121', 'E126', 'E128', 'E129', 'E130', 'E131', 'E136', 'E138', 'E139', 'E140', 'E141', 'E146', 'E148', 'E149']],
        'diabetes_com_complicacoes' => [2, ['E102', 'E103', 'E104', 'E105', 'E107', 'E112', 'E113', 'E114', 'E115', 'E117', 'E122', 'E123', 'E124', 'E125', 'E127', 'E132', 'E133', 'E134', 'E135', 'E137', 'E142', 'E143', 'E144', 'E145', 'E147']],
        'hemiplegia_paraplegia' => [2, ['G041', 'G114', 'G801', 'G802', 'G81', 'G82', 'G830', 'G831', 'G832', 'G833', 'G834', 'G839']],
        'doenca_renal' => [2, ['I120', 'I131', 'N032', 'N033', 'N034', 'N035', 'N036', 'N037', 'N052', 'N053', 'N054', 'N055', 'N056', 'N057', 'N18', 'N19', 'N250', 'Z490', 'Z491', 'Z492', 'Z940', 'Z992']],
        'neoplasia' => [2, ['C00', 'C01', 'C02', 'C03', 'C04', 'C05', 'C06', 'C07', 'C08', 'C09', 'C10', 'C11', 'C12', 'C13', 'C14', 'C15', 'C16', 'C17', 'C18', 'C19', 'C20', 'C21', 'C22', 'C23', 'C24', 'C25', 'C26', 'C30', 'C31', 'C32', 'C33', 'C34', 'C37', 'C38', 'C39', 'C40', 'C41', 'C43', 'C45', 'C46', 'C47', 'C48', 'C49', 'C50', 'C51', 'C52', 'C53', 'C54', 'C55', 'C56', 'C57', 'C58', 'C60', 'C61', 'C62', 'C63', 'C64', 'C65', 'C66', 'C67', 'C68', 'C69', 'C70', 'C71', 'C72', 'C73', 'C74', 'C75', 'C76', 'C81', 'C82', 'C83', 'C84', 'C85', 'C88', 'C90', 'C91', 'C92', 'C93', 'C94', 'C95', 'C96', 'C97']],
        'doenca_hepatica_moderada_grave' => [3, ['K704', 'K711', 'K721', 'K729', 'K765', 'K766', 'K767', 'I850', 'I859', 'I864', 'I982']],
        'neoplasia_metastatica' => [6, ['C77', 'C78', 'C79', 'C80']],
        'sida_vih' => [6, ['B20', 'B21', 'B22', 'B24']],
    ];

    /**
     * Pares de categorias onde só conta a mais grave quando ambas estão
     * presentes (mesma condição clínica, gravidades diferentes).
     */
    private const EXCLUSIVIDADE = [
        ['diabetes_sem_complicacoes', 'diabetes_com_complicacoes'],
        ['doenca_hepatica_ligeira', 'doenca_hepatica_moderada_grave'],
        ['neoplasia', 'neoplasia_metastatica'],
    ];

    /**
     * Calcula o score de Charlson para o internamento, ou null se não se
     * aplicar (sem complicações registadas — ver decisão de negócio acima).
     */
    public function calcular(Internamento $internamento): ?int
    {
        if (!$this->aplicavel($internamento)) {
            return null;
        }

        $categorias = $this->categoriasPresentes($internamento);

        return $this->pontuarCategorias($categorias) + $this->pontosIdade($internamento);
    }

    /**
     * Detalhe do cálculo (categorias encontradas, pontos de idade e total) —
     * útil para mostrar/justificar o valor calculado, não só o número final.
     */
    public function detalhar(Internamento $internamento): ?array
    {
        if (!$this->aplicavel($internamento)) {
            return null;
        }

        $categorias = $this->categoriasPresentes($internamento);
        $pontosCategorias = $this->pontuarCategorias($categorias);
        $pontosIdade = $this->pontosIdade($internamento);

        return [
            'categorias' => $categorias,
            'pontos_categorias' => $pontosCategorias,
            'pontos_idade' => $pontosIdade,
            'total' => $pontosCategorias + $pontosIdade,
        ];
    }

    /**
     * Usa a relação já carregada quando disponível (evita N+1 ao listar
     * vários internamentos com `with('complicacaoInternamentos')`). Quando
     * não está carregada, consulta a existência diretamente em vez de aceder
     * à propriedade da relação — esta define `with('complicacao','resolucaos')`
     * por omissão, o que forçaria sempre esse join mesmo só para saber se
     * existe alguma linha.
     */
    private function aplicavel(Internamento $internamento): bool
    {
        if ($internamento->relationLoaded('complicacaoInternamentos')) {
            return $internamento->complicacaoInternamentos->isNotEmpty();
        }

        return $internamento->complicacaoInternamentos()->exists();
    }

    /**
     * @return array<int, string> chaves de App\Services\CharlsonCalculatorService::CATEGORIAS
     *         presentes nas comorbilidades deste internamento, já com a
     *         exclusividade entre pares sobrepostos aplicada.
     */
    private function categoriasPresentes(Internamento $internamento): array
    {
        $codigos = $internamento->diagnosticoInternamentos
            ->reject(fn ($d) => (bool) $d->principal)
            ->pluck('diagnostico.codigo')
            ->filter()
            ->unique();

        $presentes = [];

        foreach (self::CATEGORIAS as $categoria => [, $prefixos]) {
            foreach ($codigos as $codigo) {
                if ($this->codigoNaCategoria($codigo, $prefixos)) {
                    $presentes[] = $categoria;
                    break;
                }
            }
        }

        return $this->aplicarExclusividade($presentes);
    }

    private function codigoNaCategoria(string $codigo, array $prefixos): bool
    {
        foreach ($prefixos as $prefixo) {
            if (str_starts_with($codigo, $prefixo)) {
                return true;
            }
        }

        return false;
    }

    private function aplicarExclusividade(array $categorias): array
    {
        foreach (self::EXCLUSIVIDADE as [$leve, $grave]) {
            if (in_array($leve, $categorias, true) && in_array($grave, $categorias, true)) {
                $categorias = array_diff($categorias, [$leve]);
            }
        }

        return array_values($categorias);
    }

    private function pontuarCategorias(array $categorias): int
    {
        return array_sum(array_map(fn (string $categoria) => self::CATEGORIAS[$categoria][0], $categorias));
    }

    /**
     * +1 ponto por década de idade acima dos 40 (idade à data de entrada),
     * até ao máximo de 4 pontos (>= 80 anos) — fórmula original de Charlson.
     */
    private function pontosIdade(Internamento $internamento): int
    {
        if (!$internamento->patient?->data_nascimento || !$internamento->data_entrada) {
            return 0;
        }

        $idade = Carbon::parse($internamento->patient->data_nascimento)
            ->diffInYears(Carbon::parse($internamento->data_entrada));

        if ($idade < 40) {
            return 0;
        }

        return min(4, intdiv($idade - 40, 10) + 1);
    }
}
