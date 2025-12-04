<?php

namespace Sped\Gnre\Helper;

class MDFEMapper
{
    public function map($mdfe): array
    {
        $t = $mdfe->transportadora;

        return [
            'ambiente'      => 2, // FIXME: Utilizar ambiente correto
            'ufFavorecida'  => $mdfe->codigo_uf_sefaz,
            'tipoGnre'      => '0',

            'emitente' => [
                'cnpj'        => preg_replace('/\D+/', '', $t->documento),
                'razaoSocial' => $t->razao_social,
                'endereco'    => [
                    'logradouro' => $t->logradouro,
                    'cidade'     => $t->cidade,
                    'uf'         => $t->estado,
                    'cep'        => preg_replace('/\D+/', '', $t->cep),
                    'telefone'   => preg_replace('/\D+/', '', $t->telefone),
                ],
            ],

            'itens' => [
                [
                    'receita'        => '100102', // FIXME: regra de negócio correta
                    'documentoOrigem'=> $mdfe->chave,
                    'valor'          => number_format($mdfe->valor_icms_gnre ?? 0, 2, '.', ''),
                    'dataVencimento' => date('Y-m-d'), // FIXME: regra real
                ]
            ]
        ];
    }
}
