<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Advert;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Advert controller.
 *
 * @Route("advert")ss
 */
class AdvertController extends Controller
{
    /**
     * Lists all advert entities.
     *
     * @Route("/", name="advert_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $adverts = $em->getRepository('AppBundle:Advert')->findAll();


        return $this->render('advert/index.html.twig', array(
            'adverts' => $adverts,
        ));
    }

    /**
     * Creates a new advert entity.
     *
     * @Route("/new", name="advert_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $advert = new Advert();
        $form = $this->createForm('AppBundle\Form\AdvertType', $advert);
        $form->handleRequest($request);
        /** @var User $user */
        $user = $this->getUser();


        if ($form->isSubmitted() && $form->isValid()) {

           if($form['photo']->getData()){
               $file = $advert->getPhoto();
               $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();
               $file->move(
                   $this->getParameter('photo_directory'),
                   $fileName
               );
               $advert->setPhoto($fileName);
           }

            $em = $this->getDoctrine()->getManager();
            $advert->setUser($this->getUser());

            $user->addAdvert($advert);
            $em->persist($advert);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('advert_show', array('id' => $advert->getId()));
        }

        return $this->render('advert/new.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a advert entity.
     *
     * @Route("/{id}" ,requirements={"id"="\d+"}, name="advert_show")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request, Advert $advert)
    {
        $deleteForm = $this->createDeleteForm($advert);

        $comment = new Comment();
        $commentForm = $this->createForm('AppBundle\Form\CommentType', $comment);
        $commentForm->handleRequest($request);
        $user = $advert->getUser();

        if ($commentForm->isSubmitted() && $commentForm->isValid()){
            $em = $this->getDoctrine()->getManager();
            $comment->setUser($user);
            $comment->setAdvert($advert);
            $advert->addComment($comment);
            $em->persist($advert);
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('advert_show', ['id' => $advert->getId()]);
        }
        return $this->render('advert/show.html.twig', array(
            'advert' => $advert,
            'delete_form' => $deleteForm->createView(),
            'comment_form' => $commentForm->createView()
        ));
    }



    /**
     * Finds and displays a advert entity.
     *
     * @Route("/{category}", name="advert_show_category")
     * @Method("GET")
     */
    public function showByCategoryAction($category)
    {
        $em = $this->getDoctrine()->getManager();

        $title = $em->getRepository('AppBundle:Category')->findOneByTitle($category);
        $advert = $title->getAdverts();

        return $this->render('advert/index.html.twig', array(
            'adverts' => $advert
        ));
    }


    /**
     * Displays a form to edit an existing advert entity.
     *
     * @Route("/{id}/edit", name="advert_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Advert $advert)
    {
       $deleteForm = $this->createDeleteForm($advert);
       $pathToFile = $advert->getPhoto();
        if($advert->getPhoto() !== null
            && file_exists($this->getParameter('photo_directory').'/'.$advert->getPhoto())) {
            $file = new File($this->getParameter('photo_directory').'/'.$advert->getPhoto());
            $advert->setPhoto($file);
        } else {
            $advert->setPhoto(null);
        }


        $editForm = $this->createForm('AppBundle\Form\AdvertType', $advert);
        $editForm->handleRequest($request);

        $user = $this->getUser();
        $adverts = $user->getAdverts();
        $isOk = $adverts->contains($advert);

        if($isOk === false){
            return $this->redirectToRoute('advert_index');
        }

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $advert->setUser($this->getUser());
            if($editForm['photo']->getData()){
                    $file = $advert->getPhoto();
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move(
                        $this->getParameter('photo_directory'), $fileName);
                    $advert->setPhoto($fileName);
            }else{
                $advert->setPhoto($pathToFile);
            }

            $user->addAdvert($advert);
            $em->persist($advert);
            $em->persist($user);
            $em->flush();
        }

        return $this->render('advert/edit.html.twig', array(
            'advert' => $advert,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a advert entity.
     *
     * @Route("/{id}", name="advert_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Advert $advert)
    {

        $form = $this->createDeleteForm($advert);
        $form->handleRequest($request);
        $user = $this->getUser();
        $adverts = $user->getAdverts();
        $isOk = $adverts->contains($advert);

        if($isOk === false){
            return $this->redirectToRoute('advert_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($advert);
            $em->flush();
        }

        return $this->redirectToRoute('advert_index');
    }

    /**
     * Creates a form to delete a advert entity.
     *
     * @param Advert $advert The advert entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Advert $advert)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('advert_delete', array('id' => $advert->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
