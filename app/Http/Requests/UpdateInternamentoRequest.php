<?php

namespace App\Http\Requests;

use App\Models\ComplicacaoInternamento;
use App\Models\Destino;
use App\Models\Internamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInternamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O internamento a editar, para regras que comparam datas contra o
     * registo atual (ex: data_alta <= data_saida). O parâmetro de rota
     * chama-se "internamento" (singular de `Route::resource('internamentos', ...)`).
     */
    protected function internamento(): ?Internamento
    {
        return Internamento::find($this->route('internamento'));
    }

    /**
     * O campo `servico_destino` só é obrigatório quando o destino escolhido
     * (no pedido, ou o atual do internamento se o pedido não o alterar) é
     * "Transf Interna" — não há tabela de serviços do hospital, por isso é
     * texto livre (ver decisão no plano de implementação).
     */
    protected function exigeServicoDestino(?Internamento $internamento): bool
    {
        $destinoId = $this->input('destino_id', $internamento?->destino_id);

        if (!$destinoId) {
            return false;
        }

        return Destino::where('id', $destinoId)->where('nome', 'Transf Interna')->exists();
    }

    public function rules(): array
    {
        $internamento = $this->internamento();

        return [
            'destino_id' => 'nullable|exists:destinos,id',
            'destino_detalhe_id' => 'nullable|exists:destino_detalhes,id',
            'servico_destino' => [
                $this->exigeServicoDestino($internamento) ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'data_alta' => [
                'nullable',
                'date',
                $internamento ? Rule::date()->beforeOrEqual($internamento->data_saida) : 'date',
            ],
            'origem_id' => 'nullable|exists:origems,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'clavien_dindo_id' => 'nullable|exists:clavien_dindos,id',
            'falecido' => 'nullable|boolean',
            'falecido_apos_alta' => 'nullable|boolean',
            'causas_readmissao' => 'nullable|array',
            'causas_readmissao.*' => [Rule::in(Internamento::CAUSAS_READMISSAO_OPTIONS)],
            'mortalidade_esperada' => 'nullable|boolean',
            'observacoes' => 'nullable|string|max:1000',
            'complicacao_internamentos' => 'nullable|array',
            'complicacao_internamentos.*.id' => 'nullable|exists:complicacao_internamento,id',
            'complicacao_internamentos.*.complicacao_id' => 'nullable|exists:complicacaos,id',
            'complicacao_internamentos.*.momento' => ['nullable', Rule::in(ComplicacaoInternamento::MOMENTO_OPTIONS)],
            'complicacao_internamentos.*.resolucaos' => 'nullable|array',
            'complicacao_internamentos.*.resolucaos.*.id' => 'nullable|exists:resolucaos,id',

            'atrasos_alta' => 'nullable|array',
            'atrasos_alta.*.id' => 'nullable|exists:atrasos_alta,id',
            'atrasos_alta.*.motivo_id' => 'nullable|exists:motivos_atraso,id',
            'atrasos_alta.*.data_sinalizacao_servico_social' => 'nullable|date',
            'atrasos_alta.*.data_pedido_rede' => 'nullable|date',
            'atrasos_alta.*.observacoes' => 'nullable|string|max:1000',

            'readmissao_planeada' => 'nullable|boolean',
            'readmissao_relacionada' => 'nullable|boolean',
            'consulta_pos_alta_marcada' => 'nullable|boolean',
            'data_consulta_pos_alta' => [
                'nullable',
                'date',
                $internamento?->data_saida ? Rule::date()->afterOrEqual($internamento->data_saida) : 'date',
            ],
            'contacto_telefonico_pos_alta' => 'nullable|boolean',
            'data_contacto_pos_alta' => [
                'nullable',
                'date',
                $internamento?->data_saida ? Rule::date()->afterOrEqual($internamento->data_saida) : 'date',
            ],

            'data_admissao_urgencia' => 'nullable|date',
            'episodio_urgencia' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'data_alta.date' => 'A data da alta clínica tem de ser uma data válida.',
            'destino_id.exists' => 'Destino inválido.',
            'origem_id.exists' => 'Origem inválida.',
        ];
    }
}
