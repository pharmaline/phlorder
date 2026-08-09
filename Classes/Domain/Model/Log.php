<?php
namespace Pharmaline\Phlorder\Domain\Model;

/***
 *
 * This file is part of the "phlorder Bestellung" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Christian Platt <christian.platt@pharmaline.de>, pharmaline
 *
 ***/

/**
 * Log
 */
class Log extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * timestamp
     *
     * Nullable, seit die DB-Spalte auf "datetime DEFAULT NULL" umgestellt ist
     * (vorher Legacy-Nulldatum '0000-00-00 00:00:00').
     *
     * @var \DateTime|null
     */
    protected $timestamp = null;

    /**
     * action
     *
     * @var string
     */
    protected $action = '';

	/**
     * orderid
     *
     * @var string
     */
    protected $orderid = '';
    
	/**
     * orderid
     *
     * @var string
     */
    protected $ordernumber = '';

    /**
     * result
     *
     * @var string
     */
    protected $result = '';

    /**
     * value1
     *
     * @var string
     */
    protected $value1 = '';

    /**
     * value2
     *
     * @var string
     */
    protected $value2 = '';

    /**
     * free
     *
     * @var string
     */
    protected $free = '';

    /**
     * actor
     *
     * @var string
     */
    protected $actor = '';

    /**
     * Returns the timestamp
     *
     * @return \DateTime|null $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets the timestamp
     *
     * @param \DateTime|null $timestamp
     * @return void
     */
    public function setTimestamp(?\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the action
     *
     * @return string $action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action
     *
     * @param string $action
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Returns the orderid
     *
     * @return string $orderid
     */
    public function getOrderid()
    {
        return $this->orderid;
    }

    /**
     * Sets the orderid
     *
     * @param string $orderid
     * @return void
     */
    public function setOrderid($orderid)
    {
        $this->orderid = $orderid;
    }


  /**
     * Returns the ordernumber
     *
     * @return string $orderid
     */
    public function getOrdernumber()
    {
        return $this->ordernumber;
    }

    /**
     * Sets the ordernumber
     *
     * @param string $ordernumber
     * @return void
     */
    public function setOrdernumber($ordernumber)
    {
        $this->ordernumber = $ordernumber;
    }


    /**
     * Returns the result
     *
     * @return string $result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the result
     *
     * @param string $result
     * @return void
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Returns the value1
     *
     * @return string $value1
     */
    public function getValue1()
    {
        return $this->value1;
    }

    /**
     * Sets the value1
     *
     * @param string $value1
     * @return void
     */
    public function setValue1($value1)
    {
        $this->value1 = $value1;
    }

    /**
     * Returns the value2
     *
     * @return string $value2
     */
    public function getValue2()
    {
        return $this->value2;
    }

    /**
     * Sets the value2
     *
     * @param string $value2
     * @return void
     */
    public function setValue2($value2)
    {
        $this->value2 = $value2;
    }

    /**
     * Returns the free
     *
     * @return string $free
     */
    public function getFree()
    {
        return $this->free;
    }

    /**
     * Sets the free
     *
     * @param string $free
     * @return void
     */
    public function setFree($free)
    {
        $this->free = $free;
    }

    /**
     * Returns the actor
     *
     * @return string $actor
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Sets the actor
     *
     * @param string $actor
     * @return void
     */
    public function setActor($actor)
    {
        $this->actor = $actor;
    }
}
