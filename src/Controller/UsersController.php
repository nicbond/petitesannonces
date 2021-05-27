<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EditProfileType;
use App\Form\AnnoncesType;
use App\Entity\Annonces;
use App\Entity\Images;

class UsersController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/users", name="users")
     */
    public function index(): Response
    {
        return $this->render('users/index.html.twig');
    }

    /**
     * @Route("/users/annonces", name="users_annonces")
     */
    public function userAnnonces(): Response
    {
        return $this->render('users/annonces.html.twig');
    }

    /**
     * @Route("/users/annonces/ajout", name="users_annonces_ajout")
     * @param Request $request
     * @param UserInterface $user
     */
    public function ajoutAnnonce(Request $request, UserInterface $user)
    {
        $annonce = new Annonces();
        $annonce->setUsers($user);

        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gestionImages($form, $annonce);

            $this->entityManager->persist($annonce);
            $this->entityManager->flush();
            $this->addFlash('success', 'Nouvelle annonce créée avec succès');

            return $this->redirectToRoute('users');
        }

        return $this->render('users/annonces/ajout.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/users/annonces/{id}/edit", name="users_annonces_edit")
     * @param Request $request
     * @param Annonces $annonce
     */
    public function editAnnonce(Request $request, Annonces $annonce)
    {
        $form = $this->createForm(AnnoncesType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->gestionImages($form, $annonce);

            $this->entityManager->flush();
            $this->addFlash('success', 'Votre annonce a été modifié avec succès');

            return $this->redirectToRoute('users');
        }

        return $this->render('users/annonces/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/users/profil/modifier", name="users_profil_modifier")
     * @param Request $request
     * @param UserInterface $user
     */
    public function modifierProfile(Request $request, UserInterface $user)
    {
        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre profil a été modifié avec succès');

            return $this->redirectToRoute('users');
        }

        return $this->render('users/profil/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/users/password/modifier", name="users_password_modifier")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function modifierPasswordProfile(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();

            //On vérifie si les 2 mots de passe sont identiques
            if ($request->request->get('pass') == $request->request->get('pass2')) {
                $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('pass')));
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');

                return $this->redirectToRoute('users');
            } else {
                $this->addFlash('alert', 'Les deux mots de passe ne sont pas identiques');
            }
        }

        return $this->render('users/profil/edit-password.html.twig');
    }

    /**
     * @Route("/supprime/image/{id}", name="annonces_suppression_image", methods={"DELETE"})
     */
    public function suppressionImage(Images $image, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        //On vérifie si le token est valide
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])) {

            //On récupère alors le nom de l'image
            $name = $image->getName();
            //On supprime physiquement l'image sur le disque
            unlink($this->getParameter('images_directory').'/'.$name);

            //On supprime l'entrée de la base
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($image);
            $entityManager->flush();
            return new JsonResponse(['success' => 1]);
        }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }

    private function gestionImages($form, Annonces $annonce)
    {
        //On récupére les images transmises
        $images = $form->get('images')->getData();

        //On boucle sur les images
        foreach($images as $image) {
            //On génère un nouveau nom de fichier
            $fichier = md5(uniqid()) . '.' . $image->guessExtension();

            //On copie le fichier dans le dossier uploads
            $image->move(
                $this->getParameter('images_directory'),
                $fichier
            );
            
            //On stocke l'image dans la base de données (son nom)
            $img = new Images();
            $img->setName($fichier);
            $annonce->addImage($img);
        }
    }
}
