<?php

namespace Sped\Gnre\Builder;

class GNREXML
{
    private const NS = 'http://www.gnre.pe.gov.br';

    public function build(array $data): string
    {
        // TLote_GNRE is the true GNRE XML root
        $xml = new \SimpleXMLElement(
            '<TLote_GNRE xmlns="'.self::NS.'"></TLote_GNRE>'
        );

        // Cabeçalho
        $cab = $xml->addChild('gnreCabecalho');
        $cab->addChild('versao', '2.00');
        $cab->addChild('ambiente', $data['ambiente'] ?? '2');

        // Dados GNRE
        $dados = $xml->addChild('gnreDados');

        // Identificação
        $ident = $dados->addChild('identificacao');
        $ident->addChild('ufFavorecida', $data['ufFavorecida']);
        $ident->addChild('tipoGnre', $data['tipoGnre'] ?? '0');

        // Emitente
        $emit = $dados->addChild('emitente');
        $emit->addChild('CNPJ', $data['emitente']['cnpj']);
        $emit->addChild('razaoSocial', $data['emitente']['razaoSocial']);

        $end = $emit->addChild('endereco');
        $end->addChild('logradouro', $data['emitente']['endereco']['logradouro']);
        $end->addChild('cidade', $data['emitente']['endereco']['cidade']);
        $end->addChild('UF', $data['emitente']['endereco']['uf']);
        $end->addChild('CEP', $data['emitente']['endereco']['cep']);
        $end->addChild('telefone', $data['emitente']['endereco']['telefone']);

        // Itens
        $itens = $dados->addChild('itens');

        foreach ($data['itens'] as $itemData) {
            $item = $itens->addChild('item');
            $item->addChild('receita', $itemData['receita']);
            $item->addChild('documentoOrigem', $itemData['documentoOrigem']);
            $item->addChild('valor', $itemData['valor']);
            $item->addChild('dataVencimento', $itemData['dataVencimento']);
        }

        return $xml->asXML();
    }

    public function envelop(string $signedXml): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Header/>
                <soap:Body>
                    <gnreLoteRecepcao xmlns="http://www.gnre.pe.gov.br">
                        <gnreDadosMsg>
                            <![CDATA[
            $signedXml
                            ]]>
                        </gnreDadosMsg>
                    </gnreLoteRecepcao>
                </soap:Body>
            </soap:Envelope>
            XML;
    }
}
