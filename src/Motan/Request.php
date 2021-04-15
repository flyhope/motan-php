<?php
/**
 * Copyright (c) 2009-2017. Weibo, Inc.
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *             http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */

namespace Motan;

use Motan\Utils;
use Motan\Constants;

/**
 * Motan  Request for PHP 5.6+
 *
 * <pre>
 * Motan Request
 * </pre>
 *
 * @author idevz <zhoujing00k@gmail.com>
 * @version V1.0 [created at: 2019-01-06]
 */
class Request
{
    private $_protocol;
    private $_group;
    private $_service;
    private $_method;
    private $_request_args;
    private $_request_id;
    private $_request_headers = [];
    private $_serialization;
    private $_serializer;
    private $_resp_serializer_obj = null;

    public function __construct($service, $method, ...$request_args)
    {
        if (empty($service) || empty($method)) {
            throw new \Exception("Serivce and Method must not be empty when new a Motan request.", 1);
        }
        $this->_service = $service;
        $this->_method = $method;
        $this->_request_args = $request_args;
        $pos = strpos($method, '?');
        if ($pos !== FALSE) {
            $this->_method = \substr($method, 0, $pos);
            $args = [];
            parse_str(\substr($method, $pos + 1), $args);
            foreach ($args as $key => $value) {
                $this->_request_args[0][$key] = $value;
            }
        }
        $this->_request_id = Utils::genRequestId(NULL);
    }

    public function setProtocol($protocol)
    {
        !empty($protocol) && $this->_protocol = $protocol;
        return $this;
    }

    public function setGroup($group)
    {
        !empty($group) && $this->_group = $group;
        return $this;
    }

    public function setRequestId($request_id = NULL)
    {
        $request_id != NULL && $this->_request_id = $request_id;
        return $this;
    }

    public function addHeaders($headers = [])
    {
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $this->_request_headers[$key] = $value;
            }
        }
    }

    public function getProtocol()
    {
        $protocol = !empty($this->_protocol) ? $this->_protocol : Constants::PROTOCOL_MOTAN2;
        return $protocol;
    }

    public function getRequestHeaders()
    {
        return $this->_request_headers;
    }

    public function getService()
    {
        return $this->_service;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function setMethod($method)
    {
        $this->_method = $method;
    }

    public function getRequestArgs()
    {
        return $this->_request_args;
    }

    public function buildHTTPParams()
    {
        if (\count($this->_request_args) == 1 && !empty($this->_request_args[0])) {
            $string_string_map = [];
            foreach ($this->_request_args[0] as $key => $value) {
                $string_string_map[$key] = strval($value);
            }
            $this->_request_args[0] = $string_string_map;
        }
        return $this;
    }

    public function addHTTPQueryParams($params)
    {
        if (empty($params)) {
            return;
        }
        foreach ($params as $key => $value) {
            $this->_request_args[0][$key] = $value;
        }
    }

    public function getRequestId()
    {
        return $this->_request_id;
    }

    public function getGroup()
    {
        return $this->_group;
    }

    /**
     * set response object
     *
     * @param object $obj
     *
     * @return void
     */
    public function setRespSerializerObj($obj) {
        $this->_resp_serializer_obj = $obj;
    }

    /**
     * get response object
     *
     * @return object
     */
    public function getRespSerializerObj() {
        return $this->_resp_serializer_obj;
    }

    /**
     * setSerialization sets serialization of the request
     * @param $serialization, value is string typed.such as: simple, breeze
     */
    public function setSerialization($serialization)
    {
        $this->_serialization = $serialization;
    }

    /**
     * setSerialization acquires serialization type of the request.
     * @return string value is string typed. such as: simple, breeze
     */
    public function getSerialization()
    {
        return $this->_serialization;
    }

    /**
     * getSerializer acquires serialization object of this request,
     * if serialization type not supported,null will be returned.
     * @return Serialize\Breeze|Serialize\GrpcJson|Serialize\Motan|Serialize\PB|null
     */
    public function getSerializer()
    {
        if (is_object($this->_serializer)) {
            return $this->_serializer;
        }
        if (empty($this->getSerialization())) {
            return null;
        }
        $this->_serializer = Utils::getSerializer($this->getSerialization());
        return $this->_serializer;
    }
}
