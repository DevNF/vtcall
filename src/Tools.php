<?php

namespace NFService\Vtcall;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API Vtcall
 *
 * @category  NFService
 * @package   NFService\Vtcall\Tools
 * @author    Henrique Ernandes Rebelo <rebeloehenrique at hotmail dot com>
 * @copyright 2023 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * URL base para comunicação com a API
     *
     * @var string
     */
    public static $API_URL = '';

    public function __construct(string $API_URL){
        if(empty($API_URL)) throw new Exception('É obrigatório informar o servidor', 1);
        self::$API_URL = "https://$API_URL.vtcall.com.br/API";
    }

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) :void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Função responsável por definir o token a ser utilizado para comunicação com a API
     *
     * @param string $token Token para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Retorna o token utilizado para comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getToken() :string
    {
        return $this->config['token'];
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'access_token: '.$this->config['token'],
            'Accept: application/json',
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }
        return $headers;
    }

    /**
     * Consulta os Ligacoes
     *
     * @access public
     * @param array $params
     * @return array
     */
    public function consultaLigacoes(array $params = []): array
    {
        $errors = [];
        if (!isset($params['start_date']) || empty($params['start_date'])) {
            $errors[] = 'É obrigatório o envio da start_date';
        }
        if (!empty($errors)) {
            throw new Exception(implode("\r\n", $errors), 1);
        }

        try {
            $dados = $this->get("ligacoes", $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }
            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $body,
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PATCH Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function patch(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access protected
     * @return array
     */
    protected function execute(string $path, array $opts = [], array $params = []) :array
    {
        $params = array_filter($params, function($item) {
            return $item !== 'access_token';
        }, ARRAY_FILTER_USE_KEY);

        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = self::$API_URL.$path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $key => $param) {
                if (isset($key) && !empty($key) && isset($param) && !empty($param)) {
                    $paramsJoined[] = urlencode($key)."=".urlencode($param);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlC, CURLINFO_HEADER_OUT, true);
        curl_setopt($curlC, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlC, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlC, CURLOPT_FOLLOWLOCATION, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC, CURLINFO_HEADER_OUT);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }
}