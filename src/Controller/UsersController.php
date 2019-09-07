<?php
namespace App\Controller;

use App\Entity\Card;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Security\TokenAuthenticator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class UsersController extends AbstractFOSRestController
{
    private $userRepository;
    private $em;
    private $auth;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
        $this->auth = new TokenAuthenticator($em);
    }

    /**
    * @Rest\Get("/api/users/{email}")
    */
   public function getApiUser(User $user, UserProviderInterface $userProvider, Request $request)
   {
       $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
       if ($user == null)
           return $this->view();
       if('ROLE_USER' === $user->getRoles()|| 'ROLE_ADMIN' === $user->getRoles()) {
           $user = $this->userRepository->findOneBy(['email' =>$request->get('email')]);
           $user = $this->get('serializer')->serialize($user, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['cards', 'subscription']]);
           return $this->view($user);
       }
       return $this->view();
   }

    /**
     * @Rest\Get("/api/users")
     */
    public function getApiUsers()
    {
        $users = $this->userRepository->findAll();
        $users = $this->get('serializer')->serialize($users, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['cards', 'subscription']]);
        return $this->view($users);
    }

    /**
     * @Rest\Post("/api/users")
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postApiUser(SubscriptionRepository $subscriptionRepository, User $user, Request $request)
    {
        $user->setFirstname($request->get('firstname'));
        $user->setLastname($request->get('lastname'));
        $user->setAddress($request->get('address'));
        $user->setCountry($request->get('country'));
        $user->setApiKey(uniqid());
        $user->setEmail($request->get('email'));
        $user->setCreatedAt(new \DateTime());
        $user->setSubscription($subscriptionRepository->findOneBy(['name'=>$request->get('name')]));
        $this->em->persist($user);
        $this->em->flush();
        $user = $this->get('serializer')->serialize($user, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['cards', 'subscription']]);
        return $this->view($user);
    }

    /**
     * @Rest\Patch("/api/users/{email}")
     */
    public function patchApiUser(User $user, Request $request, UserProviderInterface $userProvider,SubscriptionRepository $subscriptionRepository){
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() || 'ROLE_ADMIN' === $user->getRoles()) {
            $user = $this->userRepository->findOneBy(['email' =>$request->get('email')]);
            $user->setFirstname($request->get('firstname') ?? $user->getFirstname());
            $user->setLastname($request->get('lastname') ?? $user->getLastname());
            $user->setAddress($request->get('address') ?? $user->getAddress());
            $user->setCountry($request->get('country') ?? $user->getCountry());
            $user->setSubscription($subscriptionRepository->findOneBy(['id'=>$request->get('id')]) ?? $user->getSubscription());
            $this->em->persist($user);
            $this->em->flush();
            $user = $this->get('serializer')->serialize($user, 'json', [ObjectNormalizer::IGNORED_ATTRIBUTES => ['cards', 'subscription']]);
            return $this->view($user);
        }
        return $this->view();
    }

    /**
     * @Rest\Delete("/api/users/{email}")
     */

    public function deleteApiUser(UserProviderInterface $userProvider, Request $request)
    {
        $user = $this->auth->getUser($this->auth->getCredentials($request), $userProvider);
        if ($user == null)
            return $this->view();
        if('ROLE_USER' === $user->getRoles() || 'ROLE_ADMIN' === $user->getRoles()) {
            $user = $this->userRepository->findOneBy(['email' =>$request->get('email')]);
            $cards = $user->getCards();
            foreach ($cards as $card) {
                $this->em->remove($card);
                $this->em->flush();
            }
            $this->em->remove($user);
            $this->em->flush();
        }
        return $this->view();
    }
}
