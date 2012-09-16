<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Components\Table;

use Venne;
use Venne\Application\UI\Control;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class TableControl extends Control
{


	/** @persistent */
	public $sort;

	/** @persistent */
	public $order = 'ASC';

	/** @persistent */
	public $editId;

	/** @persistent */
	public $editForm;

	/** @persistent */
	public $createForm;

	/** @var string */
	protected $templateFile;

	/** @var \DoctrineModule\Repositories\BaseRepository */
	protected $repository;

	/** @var string */
	protected $primaryColumn;

	/** @var array */
	protected $columns = array();

	/** @var array */
	protected $actions = array();

	/** @var Form[] */
	protected $forms = array();

	/** @var int */
	protected $_formCounter = 0;

	/** @var array */
	protected $buttons = array();

	/** @var array */
	protected $globalActions = array();

	/** @var int */
	protected $paginator;

	/** @var bool */
	protected $sorter;

	/** @var \Nette\Callback */
	protected $dqlCallback;


	public function __construct($primaryColumn = 'id')
	{
		parent::__construct();

		$this->primaryColumn = $primaryColumn;
	}


	public function setTemplateFile($file)
	{
		$this->templateFile = $file;
	}


	public function enableSorter()
	{
		$this->sorter = true;
	}


	public function getRepository()
	{
		return $this->repository;
	}


	public function setRepository(\DoctrineModule\Repositories\BaseRepository $repository)
	{
		$this->repository = $repository;
	}


	public function setPaginator($itemOnPage = 10)
	{
		$this->paginator = $itemOnPage;
	}


	/**
	 * @param \Venne\Forms\FormFactory $formFactory
	 * @param null $width
	 * @param null $height
	 * @return Form
	 */
	public function addForm(\Venne\Forms\FormFactory $formFactory, $title, $width = NULL, $height = NULL)
	{
		return $this['_form_' . $this->_formCounter] = $this->forms['_form_' . $this->_formCounter++] = new Form($formFactory, $title, $width, $height);
	}


	public function addColumn($name, $title, $width = NULL, $callback = NULL)
	{
		$this->columns[$name] = array(
			'title' => $title,
			'width' => $width,
			'callback' => $callback,
		);
	}


	/**
	 * @param $name
	 * @param $title
	 * @return Button
	 */
	public function addAction($name, $title)
	{
		return $this->actions[$name] = $this[$name] = new Button($title);
	}


	/**
	 * @param $name
	 * @param $title
	 * @return Button
	 */
	public function addActionDelete($name, $title)
	{
		$_this = $this;

		$control = $this->addAction($name, $title);
		$control->onClick[] = function (Button $button, $entity) use ($_this) {
			$presenter = $button->getTable()->getPresenter();
			$_this->getRepository()->delete($entity);

			if (!$presenter->isAjax()) {
				$presenter->redirect('this');
			}
			$_this->invalidateControl('table');
			$presenter->payload->url = $presenter->link('this');
		};
		$control->onSuccess[] = function (TableControl $table) {
			$table->getPresenter()->flashMessage('Items has been removed', 'success');
		};
		return $control;
	}


	/**
	 * @param $name
	 * @param $title
	 * @return Button
	 */
	public function addActionEdit($name, $title, Form $form)
	{
		$_this = $this;

		$control = $this->addAction($name, $title);
		$control->onClick[] = function (Button $button, $entity) use ($_this, $form) {
			$table = $button->getTable();
			$presenter = $table->getPresenter();

			if (!$presenter->isAjax()) {
				$table->redirect('edit!', array('editForm' => $form->getName(), 'editId' => $entity->id));
			}
			$presenter->payload->url = $table->link('edit!', array('editForm' => $form->getName(), 'editId' => $entity->id));
			$table->editForm = $form->getName();
			$table->editId = $entity->id;
			$table->handleEdit();
		};
		return $control;
	}


	/**
	 * @param Button $button
	 * @return TableControl
	 */
	public function setGlobalAction(Button $button)
	{
		$this->globalActions[$button->getName()] = $button;

		return $this;
	}


	public function addButton($name, $title, $icon = NULL)
	{
		$this->getNavbar()->addSection($name, $title, $icon);
	}


	public function addButtonCreate($name, $title, Form $form, $icon = NULL)
	{
		$_this = $this;

		$control = $this->getNavbar()->addSection($name, $title, $icon);
		$control->onClick[] = function (\CmsModule\Components\Navbar\Section $button) use ($_this, $form) {
			$table = $_this;
			$presenter = $table->getPresenter();

			if (!$presenter->isAjax()) {
				$table->redirect('create!', array('createForm' => $form->getName()));
			}
			$presenter->payload->url = $table->link('create!', array('createForm' => $form->getName()));
			$table->createForm = $form->getName();
			$table->handleCreate();
		};
		return $control;
	}


	public function handleDoAction($name, $id)
	{
		$button = $this[$name];
		$button->onClick($button, $this->repository->find($id));
		$button->onSuccess($this);
	}


	public function handleEdit()
	{
		$this->invalidateControl('form');

		if(!$this->presenter->isAjax()) {
			$this->redirect('this');
		}

		$this->presenter->payload->url = $this->link('this');
	}


	public function handleCreate()
	{
		$this->invalidateControl('form');

		if(!$this->presenter->isAjax()) {
			$this->redirect('this');
		}

		$this->presenter->payload->url = $this->link('this');
	}


	public function render()
	{
		$this->template->columns = $this->columns;
		$this->template->actions = $this->actions;
		$this->template->globalActions = $this->globalActions;
		$this->template->primaryColumn = $this->primaryColumn;
		$this->template->paginator = $this->paginator;
		$this->template->sorter = $this->sorter;

		if ($this->templateFile) {
			$this->template->setFile($this->templateFile);
		}

		$this->template->render();
	}


	public function getItems()
	{
		$dql = $this->repository->createQueryBuilder('a');

		if ($this->dqlCallback) {
			$fn = $this->dqlCallback;
			$fn($dql);
		}

		if ($this->sorter && $this->sort) {
			$dql = $dql->orderBy(array($this->sorter => $this->sort));
		}

		if ($this->paginator) {
			$dql = $dql
				->setMaxResults($this->paginator)
				->setFirstResult(($this["vp"]->page - 1) * $this->paginator);
		}

		return $dql->getQuery()->getResult();
	}


	protected function createComponentVp()
	{
		$vp = new \CmsModule\Components\VisualPaginator;
		$pg = $vp->getPaginator();
		$pg->setItemsPerPage($this->paginator);
		$pg->setItemCount($this->repository->createQueryBuilder("a")->select("COUNT(a.{$this->primaryColumn})")->getQuery()->getSingleScalarResult());
		return $vp;
	}


	protected function createComponentActionForm()
	{
		$form = new \Venne\Forms\Form;
		$form->getElementPrototype()->class[] = 'ajax';

		// items
		$items = $form->addContainer('items');
		foreach ($this->getItems() as $entity) {
			$items->addCheckbox('item_' . $entity->{$this->primaryColumn});
		}

		// actions
		$items = array();
		foreach ($this->globalActions as $key => $item) {
			$items[$key] = $item->getLabel();
		}

		$form->addSelect('action', 'Action', $items);
		$form->addSubmit('_submit', 'Submit');
		$form->onSuccess[] = callback($this, 'formSuccess');
		return $form;
	}


	public function formSuccess($form)
	{
		$action = $form['action']->getValue();
		$button = $this[$action];

		$values = $form['items']->getValues();
		foreach ($values as $key => $value) {
			if ($value) {
				$button->onClick($button, $this->repository->find(substr($key, 5)));
			}
		}

		$button->onSuccess($this);
	}


	protected function createComponentEditForm()
	{
		$entity = $this->getRepository()->find($this->editId);

		/** @var $form \Venne\Forms\Form */
		$form = $this->forms[$this->editForm]->getFactory()->invoke($entity);
		$form->onSave[] = $this->formEditValidate;
		$form->onSuccess[] = $this->formEditSuccess;

		return $form;
	}


	public function formEditValidate(\Venne\Forms\Form $form)
	{
		try {
			$this->getRepository()->save($form->data);
		} catch (\DoctrineModule\ORM\SqlException $e) {
			if ($e->getCode() == 23000) {
				$form->addError($e->getMessage(), "warning");
				return;
			} else {
				throw $e;
			}
		}
	}


	public function formEditSuccess(\Venne\Forms\Form $form)
	{
		$this->getPresenter()->flashMessage('Item has been saved', 'success');

		if (!$this->presenter->isAjax()) {
			$this->redirect('edit!', array('editForm' => NULL, 'editId' => NULL));
		}
		$this->presenter->payload->url = $table->link('edit!', array('editForm' => NULL, 'editId' => NULL));
		$table->editForm = NULL;
		$table->editId = NULL;
		$table->handleEdit();
	}


	protected function createComponentCreateForm()
	{
		$entity = $this->getRepository()->createNew();

		/** @var $form \Venne\Forms\Form */
		$form = $this->forms[$this->createForm]->getFactory()->invoke($entity);
		$form->onSave[] = $this->formEditValidate;
		$form->onSuccess[] = $this->formCreateSuccess;

		return $form;
	}


	public function formCreateSuccess(\Venne\Forms\Form $form)
	{
		$this->getPresenter()->flashMessage('Item has been saved', 'success');

		if (!$this->presenter->isAjax()) {
			$this->redirect('create!', array('createForm' => NULL));
		}
		$this->presenter->payload->url = $table->link('create!', array('createForm' => NULL));
		$table->editForm = NULL;
		$table->editId = NULL;
		$table->handleEdit();
	}


	/**
	 * @param \Nette\Callback $dql
	 */
	public function setDql($dql)
	{
		$this->dqlCallback = $dql;
	}


	/**
	 * @return \Nette\Callback
	 */
	public function getDql()
	{
		return $this->dqlCallback;
	}


	/***************************** Navbar **************************** */


	/**
	 * @return \CmsModule\Components\Navbar\NavbarControl
	 */
	public function getNavbar()
	{
		$this->template->showNavbar = true;

		return $this['navbar'];
	}


	/**
	 * @return \CmsModule\Components\Navbar\NavbarControl
	 */
	protected function createComponentNavbar()
	{
		return new \CmsModule\Components\Navbar\NavbarControl();
	}
}