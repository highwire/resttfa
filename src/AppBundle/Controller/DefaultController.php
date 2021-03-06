<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use AppBundle\Entity\SharedSecret;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="login")
     */
    public function indexAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
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

        $sharedSecret = $this->getDoctrine()->getRepository('AppBundle:SharedSecret')->find($params['account']);

        if (!$sharedSecret) {
            return new Response(
                json_encode(array('result' => 'error', 'error' => 'Could not find account associated with' . $params['account'])),
                Response::HTTP_BAD_REQUEST,
                array('content-type' => 'application/json')
            );
        }

        $ok = $sharedSecret->verifyToken($params['token']);

        if ($ok) {
            return new Response(
                json_encode(array('result' => 'ok', 'error' => NULL)),
                Response::HTTP_OK,
                array('content-type' => 'application/json')
            );
        }
        else {
            return new Response(
                json_encode(array('result' => 'error', 'error' => 'Invalid Token')),
                Response::HTTP_BAD_REQUEST,
                array('content-type' => 'application/json')
            );
        }
    }

    /**
     * @Route("/admin", name="admin"))
     */
    public function adminAction(Request $request)
    {
        $ss = new SharedSecret();

        $form = $this->createFormBuilder($ss)
            ->add('email', EmailType::class)
            ->add('save', SubmitType::class, array('label' => 'Create TFA Shared Secret'))
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ss = $form->getData();

            // If the email already exists, load the existing one
            $existing = $this->getDoctrine()->getRepository('AppBundle:SharedSecret')->find($ss->getEmail());
            if ($existing) {
                $ss = $existing;
            }

            $ss->generateSharedSecret();
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($ss);
            $em->flush();

            $access_url = $request->getSchemeAndHttpHost() . '/fetch?account=' .$ss->getEmail() . '&access_token=' . $ss->getAccessToken();
            $message = \Swift_Message::newInstance()
                ->setSubject('Two Factor Authentication Secret')
                ->setFrom('admin@highwire.org')
                ->setTo($ss->getEmail())
                ->setBody(
                    $this->renderView(
                        'emails/fetch.html.twig',
                        array(
                            'email' => $ss->getEmail(), 
                            'fetchurl' => $access_url
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $this->addFlash(
                'notice',
                'Secret has been generated and an email sent to ' . $ss->getEmail()
            );
        }

        return $this->render('default/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/fetch")
     */
    public function fetchSharedSecretAction(Request $request)
    {

        $account = $request->query->get('account');
        $access_token = $request->query->get('access_token');

        if (empty($account) || empty($access_token)) {
            return new Response(
                '400 Bad Request. Missing query parameters',
                Response::HTTP_BAD_REQUEST
            );
        }

        $em = $this->getDoctrine()->getManager();
        $ss = $em->getRepository('AppBundle:SharedSecret')->find($account);

        $ok = $ss->verifyAccessToken($access_token);

        if (!$ok) {
            return new Response(
                '400 Bad Request. Bad access token',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Delete the one time access token to mark the secret as distributed
        $ss->markDistributed();
        $em->flush();

        return $this->render('default/fetch.html.twig', array(
            'email' => $ss->getEmail(), 
            'secret' => $ss->getSharedSecret(), 
            'qrcode' => base64_encode($ss->generateQRCode()), 
        ));
    }
}
