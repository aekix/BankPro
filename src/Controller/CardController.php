<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Repository\SubscriptionRepository;
use App\Repository\CardRepository;
use App\Repository\UserRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CardController extends AbstractFOSRestController
{
    private $cardRepository;
    private $em;
    private $auth;

    public function __construct(CardRepository $cardRepository, EntityManagerInterface $em)
    {
        $this->cardRepository = $cardRepository;
        $this->em = $em;
        $this->auth = new TokenAuthenticator($em);
    }

    /**
     * @Rest\Get("/api/cards/{id}")
     */
    public function getApiCard(Card $card, UserProviderInterface $userProvider, Request $request)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles()||'ROLE_ADMIN' === $user->getRoles()) {
            $cards = $this->cardRepository->findAll();
            foreach ($cards as $card) {
                if ($card->getId() == $request->get('id')) {
                    $card = $this->get('serializer')->serialize($card, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['user']]);
                    return $this->view($card);
                }
            }
        }
        return $this->view();
    }

    /**
     * @Rest\Get("/api/cards")
     */
    public function getApiCards(UserProviderInterface $userProvider, Request $request)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() ||'ROLE_ADMIN' === $user->getRoles()) {
            if ('ROLE_USER' === $user->getRoles())
                $cards = $user->getCards();
            else
                $cards = $this->cardRepository->findAll();
            $cards = $this->get('serializer')->serialize($cards, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['user']]);
            return $this->view($cards);
        }
        return $this->view();
    }

    /**
     * @Rest\Patch("/api/cards/{id}")
     */
    public function patchApiCard(Card $card, Request $request, UserProviderInterface $userProvider,SubscriptionRepository $subscriptionRepository, UserRepository $userRepository){
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() || 'ROLE_ADMIN' === $user->getRoles()) {
            $card->setName($request->get('name') ?? $card->getName());
            $card->setCreditCardType($request->get('creditCardType') ?? $card->getCreditCardType());
            $card->setCreditCardNumber($request->get('creditCardNumber') ?? $card->getCreditCardNumber());
            $card->setCurrencyCode($request->get('currencyCode') ?? $card->getCurrencyCode());
            $card->setValue($request->get('value') ?? $card->getValue());
            $card->setUser($userRepository->findOneBy(['apiKey'=>$request->get('X-AUTH-TOKEN')]) ?? $card->getUser());
            $this->em->persist($card);
            $this->em->flush();
            $card = $this->get('serializer')->serialize($card, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['user']]);
            return $this->view($card);
        }
        return $this->view();
    }

    /**
     * @Rest\Post("/api/cards")
     */
    public function postApiUser(SubscriptionRepository $subscriptionRepository, Request $request, UserRepository $userRepository, UserProviderInterface $userProvider)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles()||'ROLE_ADMIN' === $user->getRoles()) {
            $card = new Card();
            $card->setName($request->get('name'));
            $card->setCreditCardType($request->get('creditCardType'));
            $card->setCreditCardNumber($request->get('creditCardNumber'));
            $card->setCurrencyCode($request->get('currencyCode'));
            $card->setValue($request->get('value'));
            $card->setUser($userRepository->findOneBy(['apiKey'=>$request->get('X-AUTH-TOKEN')]));
            $this->em->persist($card);
            $this->em->flush();
            $card = $this->get('serializer')->serialize($card, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['user']]);
            return $this->view($card);
        }
        return $this->view();
    }

    /**
     * @Rest\Delete("/api/cards/{id}")
     */
    public function deleteApiCard(Card $card, UserProviderInterface $userProvider, Request $request)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() || 'ROLE_ADMIN' === $user->getRoles()) {
            $this->em->remove($card);
            $this->em->flush();
        }
        return $this->view();
    }
}
