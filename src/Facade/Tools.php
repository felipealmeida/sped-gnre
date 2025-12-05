<?php

namespace Sped\Gnre\Facade;

use Sped\Gnre\Builder\GNREXML;
use Sped\Gnre\Configuration\Setup;

use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;
use NFePHP\Common\Soap\SoapCurl;

class Tools
{
    public function __construct(
        private Setup $setup,
        private GNREXML $builder,
        private Certificate $certificate,
    ) {}

   /**
    * Assina o XML GNRE usando XMLDSig.
    *
    * Observação:
    * O Signer do sped-common já define internamente:
    * - algoritmo SHA1
    * - canonicalização
    * - inserção automática da assinatura
    *
    * Passar esses parâmetros manualmente causa erro
    * ("openssl_sign(): Argument #4 must be string|int"), porque
    * a assinatura é configurada dentro do próprio Signer.
    *
    * Portanto, basta informar o certificado, o XML e a tag raiz
    * que deve ser assinada.
    */
    private function signXml(string $xml): string
    {
        return Signer::sign(
            $this->certificate,
            $xml,
            'TLote_GNRE'
        );
    }

    /**
     * Envia o SOAP usando SoapCurl do sped-common.
     */
     private function soapSend(string $soapXml, string $url): array
     {
         $ch = curl_init();

         curl_setopt_array($ch, [
             CURLOPT_URL            => $url,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_POST           => true,
             CURLOPT_POSTFIELDS     => $soapXml,
             CURLOPT_HTTPHEADER     => [
                 'Content-Type: text/xml; charset=utf-8',
                 'SOAPAction: "http://www.gnre.pe.gov.br/ws/GnreLoteRecepcao"',
             ],
             CURLOPT_SSLCERT        => $this->certificate->getCert($this->certificate),
             CURLOPT_SSLKEY         => $this->certificate->getPrivateKey($this->certificate),
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_SSL_VERIFYHOST => 0,
             CURLOPT_TIMEOUT        => 30,
         ]);

         $body = curl_exec($ch);
         $err  = curl_error($ch);
         $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);

         return [
             'http' => $http,
             'body' => $body,
             'err'  => $err,
         ];
     }

    /**
     * API principal: monta, assina, envelopa e envia uma GNRE.
     */
    public function enviarLote(array $data): array
    {
        // 1. Gera XML não-assinado
        $unsigned = $this->builder->build($data);

        // 2. Assina
        $signed = $this->signXml($unsigned);

        // 3. Gera SOAP
        $soapXml = $this->builder->envelop($signed);

        // 4. Monta URL
        $url = 'https://www.gnre.pe.gov.br/gnreWS/services/GnreLoteRecepcao';

        // 5. Envia via SoapCurl
        return $this->soapSend($soapXml, $url);
    }
}
