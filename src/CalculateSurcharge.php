<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Altapay\Api;

use Altapay\Api\Response\Surcharge;
use Altapay\Api\Serializer\ResponseSerializer;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This method is used to calculate the surcharge beforehand,
 * based on a previously completed payment or a terminal, creditcard token, currency combo.
 */
class CalculateSurcharge extends AbstractApi
{

    /**
     * The amount to calculate the surcharge from
     *
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->unresolvedOptions['amount'] = $amount;
        return $this;
    }

    /**
     * Either 3 letter or 3 digit currency code. ISO-4217
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->unresolvedOptions['currency'] = $currency;
        return $this;
    }

    /**
     * The name of the terminal the payment will be made on.
     *
     * @param string $terminal
     * @return $this
     */
    public function setTerminal($terminal)
    {
        $this->unresolvedOptions['terminal'] = $terminal;
        return $this;
    }

    /**
     * A credit card token previously received from an eCommerce payment or an other MO/TO payment.
     *
     * @param string $creditCardToken
     * @return $this
     */
    public function setCreditCardToken($creditCardToken)
    {
        $this->unresolvedOptions['credit_card_token'] = $creditCardToken;
        return $this;
    }

    /**
     * The id of an existing payment/subscription to base calculation on
     *
     * @param string $payment_id
     * @return $this
     */
    public function setPaymentId($payment_id)
    {
        $this->unresolvedOptions['payment_id'] = $payment_id;
        return $this;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['amount']);
        $resolver->setDefined(['currency', 'terminal', 'credit_card_token', 'payment_id']);
        $resolver->setAllowedTypes('amount', ['float', 'int']);
        $resolver->setAllowedTypes('currency', ['string', 'int']);
        $resolver->setAllowedTypes('terminal', 'string');
        $resolver->setAllowedTypes('credit_card_token', 'string');
        $resolver->setAllowedTypes('payment_id', 'string');

        $resolver->setNormalizer('currency', function (Options $options, $value) {
            if (! isset($options['payment_id'])) {
                $fields = ['terminal', 'credit_card_token', 'currency'];
                foreach ($fields as $field) {
                    if (!isset($options[$field])) {
                        throw new \InvalidArgumentException(
                            sprintf('The fields "%s" is required', implode(', ', $fields))
                        );
                    }
                }
            }

            return $value;
        });

        $resolver->setNormalizer('payment_id', function (Options $options, $value) {
            $fields = ['currency', 'terminal', 'credit_card_token'];
            foreach ($fields as $field) {
                if (isset($options[$field])) {
                    throw new \InvalidArgumentException(
                        sprintf('The fields "%s" is not allowed when "payment_id" is set', implode(', ', $fields))
                    );
                }
            }

            return $value;
        });
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param Response $response
     * @return Surcharge
     */
    protected function handleResponse(Request $request, Response $response)
    {
        $body = (string) $response->getBody();
        $xml = simplexml_load_string($body);
        return ResponseSerializer::serialize(Surcharge::class, $xml->Body, false, $xml->Header);
    }

    /**
     * Url to api call
     *
     * @param array $options Resolved options
     * @return string
     */
    public function getUrl(array $options)
    {
        $query = $this->buildUrl($options);
        return sprintf('calculateSurcharge/?%s', $query);
    }
}
