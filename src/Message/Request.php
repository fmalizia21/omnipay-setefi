<?php

namespace SimoTod\OmnipaySetefi\Message;

use Exception;
use Omnipay\Common\Message\AbstractRequest;

class Request extends AbstractRequest
{
    protected $testEndpoint = 'https://test.monetaonline.it/monetaweb/payment/2/xml';
    protected $liveEndpoint = 'https://www.monetaonline.it/monetaweb/payment/2/xml';

    const OP_TYPE_INIT = 'initialize';

    public function getData()
    {
        $this->validate('id', 'password', 'amount', 'transactionId');
        return $this->getParameters();
    }

    public function setId($id)
    {
        return $this->setParameter('id', $id);
    }

    public function setPassword($password)
    {
        return $this->setParameter('password', $password);
    }

    public function setLanguage($language)
    {
        return $this->setParameter('language', $language);
    }


    public function sendData($data)
    {
        $newData = array();
        $redirectUrl = null;

        try {
            $tokenRequest = $this->httpClient->request('POST', $this->getEndpoint(), [], http_build_query($data, '', '&'));

            $xml = simplexml_load_string($tokenRequest->getBody()->getContents());

            if ($xml->errorcode) {
                $newData["reference"] = null;
                $newData['message'] = "Failure: ".$xml->errormessage->__toString();
            } else {
                $newData["reference"] = [
                    'securitytoken' => $xml->securitytoken->__toString(),
                    'paymentid'     => $xml->paymentid->__toString(),
                ];
                $newData['message'] = "Success";
                $redirectUrl = ($xml->hostedpageurl->__toString()).'?paymentId='.($xml->paymentid->__toString());
            }
        } catch (Exception $e) {
            $newData["reference"] = null;
            $newData['message'] = "Failure: ".$e->getMessage();
        }

        return $this->response = new Response($this, $newData, $redirectUrl);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}
