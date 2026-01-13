<?php

namespace Sped\Gnre\Helper;

use Sped\Gnre\Builder\GNREXML;

class MakeXml
{
    //Gera o XML TLote_GNRE a partir do array mapeado pelo MDFeMapper.
    public static function buildLote(array $data): string
    {
        $builder = new GNREXML();

        $xml = $builder->build($data);

        return $xml;
    }

    /*
     * Gera envelope SOAP com o TLote_GNRE dentro.
     * Caso seja necessário ver o XML exatamente como vai para o WebService.
     */
    public static function buildEnvelopeFromLote(string $loteXml): string
    {
        $builder = new GNREXML();

        return $builder->envelop($loteXml);
    }
}
