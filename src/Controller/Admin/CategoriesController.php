<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoriesRepository;
use App\Form\CategoriesType;
use App\Entity\Categories;

/**
 * @Route("/admin/categories", name="admin_categories_")
 */
class CategoriesController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CategoriesRepository
    */
    private $repository;
    
    public function __construct(EntityManagerInterface $entityManager, CategoriesRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        $categories = $this->repository->findAll();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories
        ]);
    }

     /**
     * @Route("/ajout", name="ajout")
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function ajoutCategorie(Request $request)
    {
        $categorie = new Categories();

        $form = $this->createForm(CategoriesType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($categorie);
            $this->entityManager->flush();
            $this->addFlash('success', 'Nouvelle catégorie créée avec succès');
            
            return $this->redirectToRoute('admin_categories_home');
        }

        return $this->render('admin/categories/ajout.html.twig', [
            'form' => $form->createView()
        ]);
    }

     /**
     * @Route("/modifier/{id}", name="modifier")
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function modifierCategorie(Request $request, $id)
    {
        $categorie = $this->repository->find($id);

        $form = $this->createForm(CategoriesType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Catégorie modifiée avec succès');
            
            return $this->redirectToRoute('admin_categories_home');
        }

        return $this->render('admin/categories/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
