<?php

/**
 * Internet.bs API Response
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs
 */
class InternetbsResponse
{
    private $status;
    private $raw;
    private $response;
    private $errors = [];
    private $headers;

    /**
     * InternetbsResponse constructor.
     *
     * @param array $api_response The API response
     */
    public function __construct(array $api_response)
    {
        $this->raw = $api_response['content'];
        $this->response = json_decode($api_response['content']);
        $this->headers = $api_response['headers'];

        // Set status
        $this->status = '400';
        if (isset($this->headers[0])) {
            $status_parts = explode(' ', $this->headers[0]);
            if (isset($status_parts[1])) {
                $this->status = (int) $status_parts[1];
            }
        }

        // Set errors
        // Some commands (e.g. Domain/Create, Domain/Transfer/Initiate) report a failure only inside the
        // "product" list: {"status":"FAILURE","product":[{"status":"FAILURE","message":"..."}]}
        $this->errors = [];
        if ($this->response && is_object($this->response)) {
            $failed = isset($this->response->status) && strtoupper((string) $this->response->status) == 'FAILURE';
            $message = isset($this->response->message) ? (string) $this->response->message : null;
            $code = isset($this->response->code) ? (int) $this->response->code : null;

            if (!empty($this->response->product) && is_array($this->response->product)) {
                foreach ($this->response->product as $product) {
                    if (isset($product->status) && strtoupper((string) $product->status) == 'FAILURE') {
                        $failed = true;
                        if ($message === null && isset($product->message)) {
                            $message = (string) $product->message;
                            $code = isset($product->code) ? (int) $product->code : $code;
                        }
                    }
                }
            }

            if ($failed) {
                $this->status = $code ?: 500;
                $this->errors[$this->status] = $message ?? 'The registrar returned FAILURE without a message.';
            }
        }
    }

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the data response from this response
     *
     * @return stdClass The data response from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return string The errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get the headers returned with this response
     *
     * @return string The headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
    }
}
