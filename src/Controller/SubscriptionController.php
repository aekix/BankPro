<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\Mapping as ORM;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations as Rest;


class SubscriptionController extends AbstractFOSRestController
{
    private $subscriptionRepository;
    private $em;
    private $auth;

    public function __construct(SubscriptionRepository $subscriptionRepository, EntityManagerInterface $em)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->em = $em;
        $this->auth = new TokenAuthenticator($em);
    }

    /**
     * @Rest\Get("/api/subscriptions/{email}")
     */
    public function getApiSubscription(Subscription $subscription)
    {
        $subscription = $this->get('serializer')->serialize($subscription, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['users']]);
        return $this->view($subscription);
    }

    /**
     * @Rest\Get("/api/subscriptions")
     */
    public function getApiSubscriptions()
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        $subscriptions = $this->get('serializer')->serialize($subscriptions, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['users']]);
        return $this->view($subscriptions);
    }

    /**
     * @Rest\Post("/api/subscriptions")
     */
    public function postApiSubscription(SubscriptionRepository $subscriptionRepository, Request $request, UserProviderInterface $userProvider)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_ADMIN' === $user->getRoles()) {
            $subscription = new Subscription();
            $subscription->setName($request->get('name'));
            $subscription->setSlogan($request->get('slogan'));
            $subscription->setUrl($request->get('url'));
            $this->em->persist($subscription);
            $this->em->flush();
            $subscription = $this->get('serializer')->serialize($subscription, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['users']]);
            return $this->view($subscription);
        }
        return $this->view();

    }

    /**
     * @Rest\Patch("/api/subscriptions/{id}")
     */
    public function patchApiSubscription(Subscription $subscription, Request $request, UserProviderInterface $userProvider,SubscriptionRepository $subscriptionRepository, UserRepository $userRepository){
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() || 'ROLE_ADMIN' === $user->getRoles()) {
            $subscription->setName($request->get('name') ?? $subscription->getName());
            $subscription->setSlogan($request->get('slogan') ?? $subscription->getSlogan());
            $subscription->setUrl($request->get('url') ?? $subscription->getUrl());
            $this->em->persist($subscription);
            $this->em->flush();
            $subscription = $this->get('serializer')->serialize($subscription, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['users']]);
            return $this->view($subscription);
        }
        return $this->view();
    }
    /**
     * @Rest\Delete("/api/subscriptions/{id}")
     */
    public function deleteApiSubscription(Subscription $subscription, UserProviderInterface $userProvider, Request $request)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_ADMIN' === $user->getRoles()) {
            if (count($subscription->getUsers()) == 0)
                $this->em->remove($subscription);
            else
                return $this->view();
            $this->em->flush();
        }
        return $this->view();
    }
}
