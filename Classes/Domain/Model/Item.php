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
 * Item
 */
class Item extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * pzn
     *
     * @var string
     */
    protected $pzn = '';

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * size
     *
     * @var string
     */
    protected $size = '';

    /**
     * dar
     *
     * @var string
     */
    protected $dar = '';

    /**
     * qty
     *
     * @var string
     */
    protected $qty = '';

    /**
     * price
     *
     * @var string
     */
    protected $price = '';

    /**
     * diff
     *
     * @var string
     */
    protected $diff = '';

    /**
     * imgfid
     *
     * @var string
     */
    protected $imgfid = '';

    /**
     * Returns the pzn
     *
     * @return string $pzn
     */
    public function getPzn()
    {
        return $this->pzn;
    }

    /**
     * Sets the pzn
     *
     * @param string $pzn
     * @return void
     */
    public function setPzn($pzn)
    {
        $this->pzn = $pzn;
    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the size
     *
     * @return string $size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size
     *
     * @param string $size
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Returns the dar
     *
     * @return string $dar
     */
    public function getDar()
    {
        return $this->dar;
    }

    /**
     * Sets the dar
     *
     * @param string $dar
     * @return void
     */
    public function setDar($dar)
    {
        $this->dar = $dar;
    }

    /**
     * Returns the qty
     *
     * @return string $qty
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Sets the qty
     *
     * @param string $qty
     * @return void
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
    }

    /**
     * Returns the price
     *
     * @return string $price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Sets the price
     *
     * @param string $price
     * @return void
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Returns the diff
     *
     * @return string $diff
     */
    public function getDiff()
    {
        return $this->diff;
    }

    /**
     * Sets the diff
     *
     * @param string $diff
     * @return void
     */
    public function setDiff($diff)
    {
        $this->diff = $diff;
    }

    /**
     * Returns the imgfid
     *
     * @return string $imgfid
     */
    public function getImgfid()
    {
        return $this->imgfid;
    }

    /**
     * Sets the imgfid
     *
     * @param string $imgfid
     * @return void
     */
    public function setImgfid($imgfid)
    {
        $this->imgfid = $imgfid;
    }
}
