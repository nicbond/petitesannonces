<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AnnoncesRepository;
use App\Entity\Annonces;

/**
 * @Route("/admin/annonces", name="admin_annonces_")
 */
class AnnoncesController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AnnoncesRepository
    */
    private $repository;
    
    public function __construct(EntityManagerInterface $entityManager, AnnoncesRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        $annonces = $this->repository->findAll();

        return $this->render('admin/annonces/index.html.twig', [
            'annonces' => $annonces
        ]);
    }

    /**
     * @Route("/activer/{id}", name="activer")
     */
    public function activer($id): Response
    {
        $annonce = $this->repository->find($id);
        $annonce->setActive(($annonce->getActive())?false:true);
        $this->entityManager->persist($annonce);
        $this->entityManager->flush();

        return new Response("true");
    }

    /**
     * @Route("/supprimer/{id}", name="supprimer", methods="DELETE")
     * @param Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function supprimer(Request $request, $id)
    {
        $annonce = $this->repository->find($id);

        if ($this->isCsrfTokenValid('delete'. $annonce->getId(), $request->get('_token'))) {
            $this->entityManager->remove($annonce);
            $this->entityManager->flush();
            $this->addFlash('success', 'Annonce supprimée avec succès');
        }
        return $this->redirectToRoute('admin_annonces_home');
    }
}
