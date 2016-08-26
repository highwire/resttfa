<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use AppBundle\Entity\SharedSecret;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

     /**
     * @Route("/tfa", name="tfa")
     */
    public function tfaAction(Request $request)
    {
        // Make sure it's a POST request
        if (!$request->isMethod('POST')) {
            throw new MethodNotAllowedHttpException(['POST'], "Please POST a JSON representation of a TFA request.");
        }

        $content = $request->getContent();

        $params = FALSE;
        if (!empty($content)) {
            $params = json_decode($content, true);
        }

        // Check to make sure valid JSON was sent
        if (empty($params['account']) || empty($params['token'])) {
            return new Response(
                json_encode(array('result' => 'error', 'error' => '400 Bad Request. Invalid JSON')),
                Response::HTTP_BAD_REQUEST,
                array('content-type' => 'application/json')
            );
        }
        
        // For now echo back acceptance
        return new Response(
            json_encode(array('result' => 'ok', 'error' => NULL)),
            Response::HTTP_OK,
            array('content-type' => 'application/json')
        );
    }

    /**
     * @Route("/admin")
     */
    public function adminAction()
    {
        // create a task and give it some dummy data for this example
        $task = new SharedSecret();

        $form = $this->createFormBuilder($task)
            ->add('SharedSecret', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Create Task'))
            ->getForm();

        return $this->render('default/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/test")
     */
    public function testAction()
    {
        $ss = new SharedSecret();
        $ss->setEmail('patrick.d.hayes@gmail.com');
        $ss->generateSharedSecret();
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($ss);
        $em->flush();

        return new Response('Saved new secret with id '.$ss->getEmail());      
    }
}
