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

/**
 * Motan Cluster for PHP 5.6+
 * 
 * <pre>
 * Cluster
 * </pre>
 * 
 * @author idevz <zhoujing00k@gmail.com>
 * @version V1.0 [created at: 2016-08-12]
 */
class Cluster
{
    private $_url_obj;
    /** @var [string] [default:Motan\Cluster\Ha\Failfast] */
    private $_ha_strategy;

    /** @var [\Motan\Cluster\LoadBalance] [default:Motan\Cluster\LoadBalance\Random] */
    private $_load_balance;

    /**
     * @return mixed
     */
    public function __construct(URL $url_obj)
    {
        $this->_url_obj = $url_obj;
        $this->_ha_strategy = Utils::getHa($this->_url_obj->getHaStrategy(), $this->_url_obj);
        $this->_load_balance = Utils::getLB($this->_url_obj->getLoadbalance(), $this->_url_obj);
    }

    public function setLoadBalance(\Motan\Cluster\LoadBalance $loadbalance)
    {
        $this->_load_balance = $loadbalance;
    }

    public function setHAStrategy(\Motan\Cluster\HaStrategy $ha)
    {
        $this->_ha_strategy = $ha;
    }

    public function getEndpoint()
    {
        $endpoint = $this->_ha_strategy->getEndpoint();
        if ($endpoint instanceof Endpointer) {
            return $endpoint;
        }
        return false;
    }

    public function getResponseHeader()
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            return $endpoint->getResponseHeader();
        }
        return false;
    }

    public function getResponseException()
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            return $endpoint->getResponseException();
        }
        return false;
    }

    public function getResponseMetadata()
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            return $endpoint->getResponseMetadata();
        }
        return false;
    }

    public function getResponse()
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            return $endpoint->getResponse();
        }
        return false;
    }

    public function getNode($request_id)
    {
        return $this->_load_balance->getNode($request_id);
    }

    public function setConnectionTimeOut($time_out = 0.1)
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            $endpoint->setConnectionTimeOut($time_out);
        }
    }

    public function setRequestTimeOut($time_out)
    {
        $endpoint = $this->getEndpoint();
        if (false !== $endpoint) {
            $endpoint->setRequestTimeOut($time_out);
        }
    }
    
    public function call(\Motan\Request $request)
    {
        return $this->_ha_strategy->call($this->_load_balance, $request);
    }

    public function doUpload(\Motan\Request $request)
    {
        return $this->_ha_strategy->doUpload($this->_load_balance, $request);
    }

    public function multiCall(array $url_objs)
    {
        $result = [];
        foreach ($url_objs as $url_obj) {
            $this->_ha_strategy = Utils::getHa($this->_url_obj->getHaStrategy(), $this->_url_obj);
            $this->_load_balance = Utils::getLB($this->_url_obj->getLoadbalance(), $this->_url_obj);
            if ($url_obj instanceof \Motan\Request) {
                $result[] = $this->_ha_strategy->call($this->_load_balance, $url_obj)->getRs();
            } else {
                $this->_url_obj = $url_obj;
                $result[] = $this->_ha_strategy->call($this->_load_balance)->getRs();
            }
        }
        
        return $result;
    }

    public function doMultiCall($request_arr)
    {
        $results = [];
        foreach ($request_arr as $request){
            $this->_ha_strategy = Utils::getHa($this->_url_obj->getHaStrategy(), $this->_url_obj);
            $this->_load_balance = Utils::getLB($this->_url_obj->getLoadbalance(), $this->_url_obj);
            $resp = NULL;
            $request_id = $request->getRequestId();
            try {
                $resp = $this->_ha_strategy->call($this->_load_balance, $request);
            } catch (\Exception $e) {
                $results[$request_id] = new \Motan\Response(NULL, $e->getMessage(), NULL);
                continue;
            }
            $results[$request_id] = $resp;
        }
        return new \Motan\MultiResponse($results);
    }

    public function getMException(\Motan\Request $request)
    {
        return $this->_multi_resp[$request->getRequestId()]->getException();
    }

    public function getMRs(\Motan\Request $request)
    {
        return $this->_multi_resp[$request->getRequestId()]->getRs();
    }
}
