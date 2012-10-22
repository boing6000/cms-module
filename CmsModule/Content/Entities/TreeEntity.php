<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Content\Entities;

use Venne;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class TreeEntity extends \DoctrineModule\Entities\IdentifiedEntity
{


	/**
	 * @ManyToOne(targetEntity="\CmsModule\Content\Entities\PageEntity", inversedBy="childrens")
	 * @JoinColumn(referencedColumnName="id", onDelete="CASCADE")
	 */
	protected $parent;

	/**
	 * @OneToOne(targetEntity="\CmsModule\Content\Entities\PageEntity", inversedBy="next")
	 * @JoinColumn(referencedColumnName="id", onDelete="SET NULL")
	 */
	protected $previous;

	/**
	 * @OneToOne(targetEntity="\CmsModule\Content\Entities\PageEntity", mappedBy="previous")
	 */
	protected $next;


	/** @Column(type="integer") */
	protected $position;

	/**
	 * @OneToMany(targetEntity="\CmsModule\Content\Entities\PageEntity", mappedBy="parent", cascade={"persist", "remove", "detach"})
	 * @OrderBy({"position" = "ASC"})
	 */
	protected $childrens;


	/**
	 * @param $type
	 */
	public function __construct()
	{
		$this->position = 1;
		$this->childrens = new \Doctrine\Common\Collections\ArrayCollection;
	}


	/**
	 * @PostRemove()
	 */
	public function onPostRemove()
	{
		$this->removeFromPosition();
	}


	/**
	 * @return mixed
	 */
	public function getParent()
	{
		return $this->parent;
	}


	protected function removeFromPosition()
	{
		$next = $this->next;
		$previous = $this->previous;

		if ($next) {
			$next->previous = $previous;
		}
		if ($previous) {
			$previous->next = $next;
		}

		if ($next) {
			$this->moveUp($next);
		}

		$this->previous = NULL;
		$this->parent = NULL;
		$this->next = NULL;
		$this->position = 1;
	}


	protected function moveUp(PageEntity $entity)
	{
		do {
			$entity->position--;
			$entity = $entity->next;
		} while ($entity);
	}


	protected function moveDown(PageEntity $entity)
	{
		do {
			$entity->position++;
			$entity = $entity->next;
		} while ($entity);
	}


	protected function getRoot(PageEntity $entity)
	{
		while ($entity->parent) {
			$entity = $entity->parent;
		}

		while ($entity->previous) {
			$entity = $entity->previous;
		}

		return $entity;
	}


	/**
	 * @param $parent
	 */
	public function setParent(PageEntity $parent = NULL, $setPrevious = NULL, PageEntity $previous = NULL)
	{
		if ($parent == $this->parent && !$setPrevious) {
			return;
		}

		if (!$parent && !$this->next && !$this->previous && !$this->parent && !$setPrevious) {
			return;
		}

		$oldParent = $this->parent;
		$oldPrevious = $this->previous;
		$oldNext = $this->next;

		// remove from position
		$this->removeFromPosition();


		if ($parent) {
			$this->parent = $parent;

			if ($setPrevious) {
				if ($previous) {
					if ($previous->next) {
						$this->moveDown($previous->next);
						$previous->next->previous = $this;
					}
					$this->next = $previous->next;

					$this->previous = $previous;
					$this->position = $this->previous->position + 1;
					$previous->next = $this;
				} else {
					$this->next = $parent->getChildrens()->first() ? : NULL;
					$this->previous = NULL;
					if ($this->next) {
						$this->next->previous = $this;
						$this->moveDown($this->next);
					}
					$this->position = 1;
				}
			} else {
				$this->previous = $parent->getChildrens()->last() ? : NULL;
				$this->next = NULL;
				if ($this->previous) {
					$this->previous->next = $this;
					$this->position = $this->previous->position + 1;
				}
			}

			$parent->childrens[] = $this;
		} else {
			if ($setPrevious) {
				if ($previous) {
					if ($previous->next) {
						$this->moveDown($previous->next);
						$previous->next->previous = $this;
					}
					$this->next = $previous->next;

					$this->previous = $previous;
					$this->position = $this->previous->position + 1;
					$previous->next = $this;
				} else {
					$this->next = $this->getRoot($oldNext ? : ($oldParent ? : ($oldPrevious)));
					if ($this->next) {
						$this->next->previous = $this;
						$this->moveDown($this->next);
					}
					$this->position = 1;
				}
			}
		}
	}


	/**
	 * @param $childrens
	 */
	public function setChildrens($childrens)
	{
		$this->childrens = $childrens;
	}


	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getChildrens()
	{
		return $this->childrens;
	}


	public function getPrevious()
	{
		return $this->previous;
	}


	public function getNext()
	{
		return $this->next;
	}


	public function setPosition($position)
	{
		$this->position = $position;
	}


	public function getPosition()
	{
		return $this->position;
	}
}
