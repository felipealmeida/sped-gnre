<?php

/**
 * Este arquivo é parte do programa GNRE PHP
 * GNRE PHP é um software livre; você pode redistribuí-lo e/ou
 * modificá-lo dentro dos termos da Licença Pública Geral GNU como
 * publicada pela Fundação do Software Livre (FSF); na versão 2 da
 * Licença, ou (na sua opinião) qualquer versão.
 * Este programa é distribuído na esperança de que possa ser  útil,
 * mas SEM NENHUMA GARANTIA; sem uma garantia implícita de ADEQUAÇÃO a qualquer
 * MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a
 * Licença Pública Geral GNU para maiores detalhes.
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com este programa, se não, escreva para a Fundação do Software
 * Livre(FSF) Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Sped\Gnre\Webservice;

use Sped\Gnre\Configuration\Setup;

/**
 * Classe que realiza a conexão com o webservice da SEFAZ com a
 * configuração definida em alguma classe que implementa \Sped\Gnre\Configuration\Interfaces\Setup e
 * para o envido das informações é utilizado o curl
 * @package     gnre
 * @subpackage  webservice
 * @author      Matheus Marabesi <matheus.marabesi@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-howto.html GPL
 * @version     1.0.0
 */
class Connection
{

    /**
     * Armazena todas as opções desejadas para serem incluídas no curl()
     * @var array
     */
    private $curlOptions = array();

    /**
     * @var \Sped\Gnre\Configuration\Setup
     */
    private $setup;

    private ?string $certPathOverride = null;
    private ?string $keyPathOverride  = null;

    /**
     * Inicia os parâmetros com o curl para se comunicar com o webservice da SEFAZ.
     *
     * @param  \Sped\Gnre\Configuration\Setup $setup
     * @param  array  $headers
     * @param  string $data
     * @param  string|null $certPath  Caminho para certificado PEM (opcional)
     * @param  string|null $keyPath   Caminho para chave privada PEM (opcional)
     */
    public function __construct(Setup $setup, $headers, $data, $certPath = null, $keyPath = null)
    {
        $this->setup = $setup;

        if (!empty($certPath)) {
	    $this->certPathOverride = $certPath;
        }

        if (!empty($keyPath)) {
            $this->keyPathOverride  = $keyPath;
        }

        $this->curlOptions = array(
            CURLOPT_PORT => 443,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_CAINFO => '/etc/ssl/certs/ca-certificates.crt',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_VERBOSE => $setup->getDebug(),
        );

        // Caso o ToolBuilder forneça PEMs, priorize eles
        if ($this->certPathOverride && $this->keyPathOverride) {
            $this->curlOptions[CURLOPT_SSLCERT]     = $this->certPathOverride;
            $this->curlOptions[CURLOPT_SSLKEY]      = $this->keyPathOverride;
            $this->curlOptions[CURLOPT_SSLCERTTYPE] = 'PEM';
        } else {
            // Caso contrário, usa o PFX como antes
            $this->curlOptions[CURLOPT_SSLCERT]     = $setup->getPfxPath();
            $this->curlOptions[CURLOPT_SSLCERTTYPE] = 'P12';
            $this->curlOptions[CURLOPT_SSLKEYPASSWD] = $setup->getCertificatePassword();
        }

        $ip = $setup->getProxyIp();
        $port = $setup->getProxyPort();

        if (!empty($ip) && $port) {
            $this->curlOptions[CURLOPT_HTTPPROXYTUNNEL] = 1;
            $this->curlOptions[CURLOPT_PROXYTYPE] = 'CURLPROXY_HTTP';
            $this->curlOptions[CURLOPT_PROXY] = $setup->getProxyIp() . ':' . $setup->getProxyPort();
        }
    }

    /**
     * Retorna as opções definidas para o curl
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Com esse método é possível adicionar novas opções ou alterar o valor das
     * opções exitentes antes de realizar a requisição para o web service,
     * exemplo de utilização com apenas uma opção:
     * <pre>
     * $connection->addCurlOption(
     * array(
     *       CURLOPT_PORT => 123
     *  )
     * );
     * </pre>
     * Exemplo de utilização com mais de uma opção :
     * <pre>
     * $connection->addCurlOption(
     * array(
     *       CURLOPT_SSLVERSION => 6,
     *       CURLOPT_SSL_VERIFYPEER => 1
     *  )
     * );
     * </pre>
     *
     * @param array $option
     * @return \Sped\Gnre\Webservice\Connection
     */
    public function addCurlOption(array $option)
    {
        foreach ($option as $key => $value) {
            $this->curlOptions[$key] = $value;
        }

        return $this;
    }

    /**
     * Realiza a requisição ao webservice desejado através do curl() do php
     * @param  string  $url  String com a URL que será enviada a requisição
     * @since  1.0.0
     * @return string|boolean Caso a requisição não seja feita com sucesso false, caso contrário um XML formatado
     */
    public function doRequest($url)
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, $this->curlOptions);
        $ret = curl_exec($curl);

        $xml = $ret;

        if ($this->setup->getDebug()) {
            print_r(curl_getinfo($curl));
        }

        if ($xml === false || $xml === '') {
            $xml = curl_error($curl);
        }

        curl_close($curl);

        return $xml;
    }
}
