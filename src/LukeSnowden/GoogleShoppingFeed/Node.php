<?php

namespace LukeSnowden\GoogleShoppingFeed;

class Node
{
    /**
     * [$name description]
     * @var string|null
     */
    protected $name = null;

    /**
     * [$namespace description]
     * @var string|null
     */
    protected $_namespace = null;

    /**
     * [$value description]
     * @var string
     */
    protected $value = '';

    /**
     * [$cdata description]
     * @var boolean
     */
    protected $cdata = false;

    /**
     * Node constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function _namespace($value)
    {
        $this->_namespace = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function addCdata()
    {
        $this->cdata = true;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->{$key};
    }

    /**
     * @param \SimpleXMLElement $parent
     */
    public function attachNodeTo(\SimpleXMLElement $parent)
    {
        if ($this->cdata && !preg_match("#^<!\[CDATA#is", $this->value)) {
            $this->value = "<![CDATA[{$this->value}]]>";
        }
        $parent->addChild($this->name, '', $this->_namespace);
        $parent->{$this->name} = $this->value;
    }
}
