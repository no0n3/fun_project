<?php
namespace components;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Curl {
    private $curl;

    public function __construct() {
        $this->curl = curl_init();
    }

    public function setOptions($options) {
        if (empty($options) || !is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            curl_setopt($this->curl, $name, $value);
        }
    }

    public function setHeader($options) {
        $this->setOpt(CURLOPT_HTTPHEADER, $options);
    }

    public function setOpt($name, $value) {
        curl_setopt($this->curl, $name, $value);
    }

    public function sendGet($url, $params = []) {
        return $this->send($url . '?' . http_build_query($params));
    }

    public function sendPost($url, $params = []) {
        $this->setOpt(CURLOPT_POST, 1);
        $this->setOpt(CURLOPT_POSTFIELDS, $params);
        return $this->send(
            empty($url) ? $url : ($url . '?' . http_build_query($params))
        );
    }

    public function sendHead($url, $params = []) {
        $this->setOpt(CURLOPT_NOBODY, 1);
        return $this->send(
            empty($url) ? $url : ($url . '?' . http_build_query($params))
        );
    }

    private function send($url, $b = true) {
        curl_setopt($this->curl, CURLOPT_URL, $url);

        $response = curl_exec($this->curl);
        curl_close($this->curl);

        $headers = $this->getHeadersFromResponse($response);

        if ($b) {
            $resp = new \stdClass();
            $resp->body = substr($response, strpos($response, "\r\n\r\n") + strlen("\r\n\r\n"));
            $resp->header = $headers;

            return $resp;
        } else {
            return $response;
        }
    }

    function getHeadersFromResponse($response) {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    public static function sendRequest($url, $params = [], $headers = []) {
        $i = new Curl();
        $i->setHeader($headers);
        return $i->sendGet($url, $params);
    }
}
