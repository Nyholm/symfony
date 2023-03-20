<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;

/**
 * @template Tk of \Stringable
 * @template Tv of object
 */
class PhpstanExtractableDummy extends AbstractDummy
{
    /** @var mixed */
    public $mixed;

    /** @var bool */
    public $bool;

    /** @var boolean */
    public $boolean;

    /** @var true */
    public $true;

    /** @var false */
    public $false;

    /** @var int */
    public $int;

    /** @var integer */
    public $integer;

    /** @var float */
    public $float;

    /** @var string */
    public $string;

    /** @var resource */
    public $resource;

    /** @var object */
    public $object;

    /** @var callable */
    public $callable;

    /** @var array */
    public $array;

    /** @var list */
    public $list;

    /** @var iterable */
    public $iterable;

    /** @var non-empty-array */
    public $nonEmptyArray;

    /** @var non-empty-list */
    public $nonEmptyList;

    /** @var null */
    public $null;

    /** @var self */
    public $self;

    /** @var static */
    public $static;

    /** @var parent */
    public $parent;

    /** @var scoped */
    public $scoped;

    /** @var ClassicDummy */
    public $sameNamespace;

    /** @var DummyBackedEnum */
    public $use;

    /** @var ?int */
    public $nullable;

    /** @var int|string */
    public $union;

    /** @var int&string */
    public $intersection;

    /** @var list<string> */
    public $genericList;

    /** @var array<string> */
    public $genericArrayList;

    /** @var array<string, string> */
    public $genericDict;

    /** @var string[] */
    public $squareBracketList;

    /** @var array{foo: int, bar: string} */
    public $bracketList;

    /** @var array{} */
    public $emptyBracketList;

    /** @var \ArrayIterator<Tk, Tv> */
    public $generic;

    /** @var Tv */
    public $genericParameter;

    public $undefined;

    /**
     * @param mixed $_
     *
     * @return mixed
     */
    public function mixed($_)
    {
        return $this->mixed;
    }

    /**
     * @param bool $_
     *
     * @return bool
     */
    public function bool($_)
    {
        return $this->bool;
    }

    /**
     * @param boolean $_
     *
     * @return boolean
     */
    public function boolean($_)
    {
        return $this->boolean;
    }

    /**
     * @param true $_
     *
     * @return true
     */
    public function true($_)
    {
        return $this->true;
    }

    /**
     * @param false $_
     *
     * @return false
     */
    public function false($_)
    {
        return $this->false;
    }

    /**
     * @param int $_
     *
     * @return int
     */
    public function int($_)
    {
        return $this->int;
    }

    /**
     * @param int $_
     *
     * @return int
     */
    public function integer($_)
    {
        return $this->integer;
    }

    /**
     * @param float $_
     *
     * @return float
     */
    public function float($_)
    {
        return $this->float;
    }

    /**
     * @param string $_
     *
     * @return string
     */
    public function string($_)
    {
        return $this->string;
    }

    /**
     * @param resource $_
     *
     * @return resource
     */
    public function resource($_)
    {
        return $this->resource;
    }

    /**
     * @param object $_
     *
     * @return object
     */
    public function object($_)
    {
        return $this->object;
    }

    /**
     * @param callable $_
     *
     * @return callable
     */
    public function callable($_)
    {
        return $this->callable;
    }

    /**
     * @param array $_
     *
     * @return array
     */
    public function array($_)
    {
        return $this->array;
    }

    /**
     * @param list $_
     *
     * @return list
     */
    public function list($_)
    {
        return $this->list;
    }

    /**
     * @param iterable $_
     *
     * @return iterable
     */
    public function iterable($_)
    {
        return $this->iterable;
    }

    /**
     * @param non-empty-array $_
     *
     * @return non-empty-array
     */
    public function nonEmptyArray($_)
    {
        return $this->nonEmptyArray;
    }

    /**
     * @param non-empty-list $_
     *
     * @return non-empty-list
     */
    public function nonEmptyList($_)
    {
        return $this->nonEmptyList;
    }

    /**
     * @param null $_
     *
     * @return null
     */
    public function null($_)
    {
        return $this->null;
    }

    /**
     * @param self $_
     *
     * @return self
     */
    public function self($_)
    {
        return $this->self;
    }

    /**
     * @param static $_
     *
     * @return static
     */
    public function static($_)
    {
        return $this->static;
    }

    /**
     * @param parent $_
     *
     * @return parent
     */
    public function parent($_)
    {
        return $this->parent;
    }

    /**
     * @param scoped $_
     *
     * @return scoped
     */
    public function scoped($_)
    {
        return $this->scoped;
    }

    /**
     * @param DummyBackedEnum $_
     *
     * @return DummyBackedEnum
     */
    public function use($_)
    {
        return $this->use;
    }

    /**
     * @param ClassicDummy $_
     *
     * @return ClassicDummy
     */
    public function sameNamespace($_)
    {
        return $this->sameNamespace;
    }

    /**
     * @param int|string $_
     *
     * @return int|string
     */
    public function union($_)
    {
        return $this->union;
    }

    /**
     * @param ?int $_
     *
     * @return ?int
     */
    public function nullable($_)
    {
        return $this->nullable;
    }

    /**
     * @param int&string $_
     *
     * @return int&string
     */
    public function intersection($_)
    {
        return $this->intersection;
    }

    /**
     * @param list<string> $_
     *
     * @return list<string>
     */
    public function genericList($_)
    {
        return $this->genericList;
    }

    /**
     * @param array<string> $_
     *
     * @return array<string>
     */
    public function genericArrayList($_)
    {
        return $this->genericArrayList;
    }

    /**
     * @param array<string, string> $_
     *
     * @return array<string, string>
     */
    public function genericDict($_)
    {
        return $this->genericDict;
    }

    /**
     * @param string[] $_
     *
     * @return string[]
     */
    public function squareBracketList($_)
    {
        return $this->squareBracketList;
    }

    /**
     * @param array{foo: int, bar: string} $_
     *
     * @return array{foo: int, bar: string}
     */
    public function bracketList($_)
    {
        return $this->bracketList;
    }

    /**
     * @param array{} $_
     *
     * @return array{}
     */
    public function emptyBracketList($_)
    {
        return $this->emptyBracketList;
    }

    /**
     * @param \ArrayIterator<Tk, Tv> $_
     *
     * @return \ArrayIterator<Tk, Tv>
     */
    public function generic($_)
    {
        return $this->generic;
    }

    /**
     * @param Tv $_
     *
     * @return Tv
     */
    public function genericParameter($_)
    {
        return $this->genericParameter;
    }

    public function void(): void
    {
    }

    public function never(): never
    {
        exit;
    }

    public function undefined($_)
    {
        return $this->undefined;
    }
}
