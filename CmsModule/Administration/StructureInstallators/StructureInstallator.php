<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Administration\StructureInstallators;

use CmsModule\Pages\Errors\Error403PageEntity;
use CmsModule\Pages\Errors\Error404PageEntity;
use CmsModule\Pages\Errors\Error500PageEntity;
use CmsModule\Security\Entities\PermissionEntity;
use CmsModule\Security\Entities\RoleEntity;
use Doctrine\ORM\EntityManager;
use Nette\Object;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class StructureInstallator extends Object implements IStructureInstallator
{

	/** @var EntityManager */
	private $entityManager;


	/**
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}


	public function run()
	{
		$this->createPages();
		$this->createAccessList();
	}

	private function createPages()
	{
		$em = $this->entityManager;

		$layout = $em->getRepository('CmsModule\Content\Entities\LayoutEntity')->find(1);

		// pages
		$textPage = new \CmsModule\Pages\Text\PageEntity;
		$textPage
			->getExtendedMainRoute()
			->setName('Main page')
			->getRoute()
			->setCopyLayoutFromParent(FALSE)
			->setLayout($layout)
			->setText('Hello, this is main page of this website.')
			->setPublished(TRUE);

		$userPage = new \CmsModule\Pages\Users\PageEntity;
		$userPage
			->getPage()
			->setParent($textPage->getPage());
		$userPage
			->getExtendedMainRoute()
			->setName('Users')
			->getRoute()
			->setText('List of users.')
			->setPublished(TRUE);

		$tagsPage = new \CmsModule\Pages\Tags\PageEntity;
		$tagsPage
			->getPage()
			->setParent($textPage->getPage());
		$tagsPage
			->getExtendedMainRoute()
			->setName('Tags')
			->getRoute()
			->setText('List of tags.')
			->setPublished(TRUE);

		$error404Page = new  Error404PageEntity;
		$error404Page
			->getPage()
			->setParent($textPage->getPage());
		$error404Page
			->getExtendedMainRoute()
			->setName('Page not found')
			->getRoute()
			->setText('404')
			->setPublished(TRUE);

		$error403Page = new  Error403PageEntity;
		$error403Page
			->getPage()
			->setParent($textPage->getPage());
		$error403Page
			->getExtendedMainRoute()
			->setName('Forbidden')
			->getRoute()
			->setText('403')
			->setPublished(TRUE);

		$error500Page = new  Error500PageEntity;
		$error500Page
			->getPage()
			->setParent($textPage->getPage());
		$error500Page
			->getExtendedMainRoute()
			->setName('Internal server error')
			->getRoute()
			->setText('500')
			->setPublished(TRUE);

		$sitemapPage = new \CmsModule\Pages\Sitemap\PageEntity;
		$sitemapPage
			->getPage()
			->setParent($textPage->getPage());
		$sitemapPage
			->getExtendedMainRoute()
			->setName('Site map')
			->getRoute()
			->setPublished(TRUE);

		$em->persist($textPage);
		$em->persist($userPage);
		$em->persist($tagsPage);
		$em->persist($error403Page);
		$em->persist($error404Page);
		$em->persist($error500Page);
		$em->persist($sitemapPage);
		$em->flush();
	}


	private function createAccessList()
	{
		$em = $this->entityManager;


		// roles
		$admin = new RoleEntity;
		$admin->setName('admin');

		$registered = new RoleEntity;
		$registered->setName('registered');

		$manager = new RoleEntity;
		$manager->setName('manager');
		$manager->setParent($registered);

		$editor = new RoleEntity;
		$editor->setName('editor');
		$editor->setParent($manager);

		$contentManager  = new RoleEntity;
		$contentManager->setName('contentManager');
		$contentManager->setParent($editor);

		$aclManager = new RoleEntity;
		$aclManager->setName('aclManager');
		$aclManager->setParent($manager);

		$em->persist($admin);
		$em->persist($registered);
		$em->persist($editor);
		$em->persist($manager);
		$em->persist($contentManager);
		$em->persist($aclManager);
		$em->flush();


		// permissions
		$em->persist(new PermissionEntity($manager, 'CmsModule\Administration\Presenters\PanelPresenter'));
		$em->persist(new PermissionEntity($manager, 'CmsModule\Administration\Presenters\DashboardPresenter'));
		$em->persist(new PermissionEntity($manager, 'CmsModule\Administration\Presenters\AccountPresenter', 'show'));

		$em->persist(new PermissionEntity($editor, 'CmsModule\Administration\Presenters\ContentPresenter'));
		$em->persist(new PermissionEntity($editor, 'CmsModule\Administration\Presenters\TagPresenter'));
		$em->persist(new PermissionEntity($editor, 'CmsModule\Administration\Presenters\FilesPresenter'));

		$em->persist(new PermissionEntity($contentManager, 'CmsModule\Administration\Presenters\LayoutsPresenter'));
		$em->persist(new PermissionEntity($contentManager, 'CmsModule\Administration\Presenters\TemplatesPresenter'));
		$em->persist(new PermissionEntity($contentManager, 'CmsModule\Administration\Presenters\LanguagePresenter'));
		$em->persist(new PermissionEntity($contentManager, 'CmsModule\Administration\Presenters\InformationsPresenter'));

		$em->persist(new PermissionEntity($aclManager, 'CmsModule\Administration\Presenters\AccountPresenter'));
		$em->persist(new PermissionEntity($aclManager, 'CmsModule\Administration\Presenters\UsersPresenter'));
		$em->persist(new PermissionEntity($aclManager, 'CmsModule\Administration\Presenters\RolesPresenter'));

		$em->flush();
	}

}
