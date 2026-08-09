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
 * Order
 */
class Order extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * phluserfid
     *
     * @var int
     */
    protected $phluserfid = 0;

    /**
     * feusefrid
     *
     * @var int
     */
    protected $feusefrid = 0;


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
     * orderid
     *
     * @var string
     */
    protected $orderid = '';
    
    /**
     * ordernumber
     *
     * @var string
     */
    protected $ordernumber = '';

    /**
     * status
     *
     * @var string
     */
    protected $status = '';

    /**
     * salutation
     *
     * @var string
     */
    protected $salutation = '';

    /**
     * company
     *
     * @var string
     */
    protected $company = '';

    /**
     * lastName
     *
     * @var string
     */
    protected $lastName = '';

    /**
     * firstName
     *
     * @var string
     */
    protected $firstName = '';

    /**
     * address
     *
     * @var string
     */
    protected $address = '';

    /**
     * zip
     *
     * @var string
     */
    protected $zip = '';

    /**
     * city
     *
     * @var string
     */
    protected $city = '';

    /**
     * phone
     *
     * @var string
     */
    protected $phone = '';

    /**
     * email
     *
     * @var string
     */
    protected $email = '';

    /**
     * mobil
     *
     * @var string
     */
    protected $mobil = '';

    /**
     * delivery
     *
     * @var string
     */
    protected $delivery = '';

    /**
     * payment
     *
     * @var string
     */
    protected $payment = '';

    /**
     * iBAN
     *
     * @var string
     */
    protected $iBAN = '';


    
    /**
     * orderImage
     *
    * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    #[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]
    protected $orderImage = null;

    /**
     * note
     *
     * @var string
     */
    protected $note = '';

    /**
     * tolog
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Log>
     */
    #[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]
    protected $tolog = null;

    /**
     * toitem
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Item>
     */
    #[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]
    protected $toitem = null;

    /**
     * Returns the phluserfid
     *
     * @return int $phluserfid
     */
    public function getPhluserfid()
    {
        return $this->phluserfid;
    }

    /**
     * Sets the phluserfid
     *
     * @param int $phluserfid
     * @return void
     */
    public function setPhluserfid($phluserfid)
    {
        $this->phluserfid = $phluserfid;
    }

    /**
     * Returns the feusefrid
     *
     * @return int $feusefrid
     */
    public function getFeusefrid()
    {
        return $this->feusefrid;
    }

    /**
     * Sets the feusefrid
     *
     * @param int $feusefrid
     * @return void
     */
    public function setFeusefrid($feusefrid)
    {
        $this->feusefrid = $feusefrid;
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
     * Returns the status
     *
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the salutation
     *
     * @return string $salutation
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * Sets the salutation
     *
     * @param string $salutation
     * @return void
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * Returns the company
     *
     * @return string $company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Sets the company
     *
     * @param string $company
     * @return void
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

 
    /**
     * Returns the lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Sets the lastName
     *
     * @param string $lastName
     * @return void
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Returns the firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Sets the firstName
     *
     * @param string $firstName
     * @return void
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
    	$this->orderImage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->tolog = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->toitem = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the address
     *
     * @return string $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Sets the address
     *
     * @param string $address
     * @return void
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Returns the zip
     *
     * @return string $zip
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Sets the zip
     *
     * @param string $zip
     * @return void
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Returns the city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets the city
     *
     * @param string $city
     * @return void
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Returns the phone
     *
     * @return string $phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Sets the phone
     *
     * @param string $phone
     * @return void
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Returns the email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the email
     *
     * @param string $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the mobil
     *
     * @return string $mobil
     */
    public function getMobil()
    {
        return $this->mobil;
    }

    /**
     * Sets the mobil
     *
     * @param string $mobil
     * @return void
     */
    public function setMobil($mobil)
    {
        $this->mobil = $mobil;
    }

    /**
     * Returns the delivery
     *
     * @return string $delivery
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Sets the delivery
     *
     * @param string $delivery
     * @return void
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Returns the payment
     *
     * @return string $payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Sets the payment
     *
     * @param string $payment
     * @return void
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * Returns the iBAN
     *
     * @return string $iBAN
     */
    public function getIBAN()
    {
        return $this->iBAN;
    }

    /**
     * Sets the iBAN
     *
     * @param string $iBAN
     * @return void
     */
    public function setIBAN($iBAN)
    {
        $this->iBAN = $iBAN;
    }


    
    /**
 	* sets the OrderImage
 	*
 	* @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $orderImage
 	*
 	* @return void
 	*/
	public function setOrderImage($orderImage) {
		$this->orderImage = $orderImage;
	}

	/**
	 * get the orderImage
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getOrderImage() {
		return $this->orderImage;
	}

    /**
     * Returns the note
     *
     * @return string $note
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Sets the note
     *
     * @param string $note
     * @return void
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Adds a Log
     *
     * @param \Pharmaline\Phlorder\Domain\Model\Log $tolog
     * @return void
     */
    public function addTolog(\Pharmaline\Phlorder\Domain\Model\Log $tolog)
    {
        $this->tolog->attach($tolog);
    }

    /**
     * Removes a Log
     *
     * @param \Pharmaline\Phlorder\Domain\Model\Log $tologToRemove The Log to be removed
     * @return void
     */
    public function removeTolog(\Pharmaline\Phlorder\Domain\Model\Log $tologToRemove)
    {
        $this->tolog->detach($tologToRemove);
    }

    /**
     * Returns the tolog
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Log> $tolog
     */
    public function getTolog()
    {
        return $this->tolog;
    }

    /**
     * Sets the tolog
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Log> $tolog
     * @return void
     */
    public function setTolog(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $tolog)
    {
        $this->tolog = $tolog;
    }

    /**
     * Adds a Item
     *
     * @param \Pharmaline\Phlorder\Domain\Model\Item $toitem
     * @return void
     */
    public function addToitem(\Pharmaline\Phlorder\Domain\Model\Item $toitem)
    {
        $this->toitem->attach($toitem);
    }

    /**
     * Removes a Item
     *
     * @param \Pharmaline\Phlorder\Domain\Model\Item $toitemToRemove The Item to be removed
     * @return void
     */
    public function removeToitem(\Pharmaline\Phlorder\Domain\Model\Item $toitemToRemove)
    {
        $this->toitem->detach($toitemToRemove);
    }

    /**
     * Returns the toitem
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Item> $toitem
     */
    public function getToitem()
    {
        return $this->toitem;
    }

    /**
     * Sets the toitem
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pharmaline\Phlorder\Domain\Model\Item> $toitem
     * @return void
     */
    public function setToitem(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $toitem)
    {
        $this->toitem = $toitem;
    }
}
