<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Dns;

class ResourceRecordSetsListResponse extends \Google\Collection
{
  protected $collection_key = 'rrsets';
  protected $headerType = ResponseHeader::class;
  protected $headerDataType = '';
  public $header;
  /**
   * @var string
   */
  public $kind;
  /**
   * @var string
   */
  public $nextPageToken;
  protected $rrsetsType = ResourceRecordSet::class;
  protected $rrsetsDataType = 'array';
  public $rrsets;

  /**
   * @param ResponseHeader
   */
  public function setHeader(ResponseHeader $header)
  {
    $this->header = $header;
  }
  /**
   * @return ResponseHeader
   */
  public function getHeader()
  {
    return $this->header;
  }
  /**
   * @param string
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param string
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param ResourceRecordSet[]
   */
  public function setRrsets($rrsets)
  {
    $this->rrsets = $rrsets;
  }
  /**
   * @return ResourceRecordSet[]
   */
  public function getRrsets()
  {
    return $this->rrsets;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ResourceRecordSetsListResponse::class, 'Google_Service_Dns_ResourceRecordSetsListResponse');
