<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Repository\FriendshipRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/addFriend/{friendId}",name="add_friend")
     */
    public function suce(
        HttpClientInterface $httpClient,
        FriendshipRepository $friendshipRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        $friendId
    ): Response
    {
        if($friendshipRepository->isRequestWaiting($this->getUser()->getId(),$userRepository->find($friendId)->getId())){
            $friendship = $friendshipRepository->isRequestWaiting($this->getUser()->getId(),$userRepository->find($friendId)->getId());
            $friendship->setAccepted(true);
            $entityManager->persist($friendship);
            $entityManager->flush();
            return New Response('Demande d\'invitation acceptée ! 😊');
        }
        elseif($friendshipRepository->isRequestSended($this->getUser()->getId(),$userRepository->find($friendId)->getId())){
            return New Response('Tu as déjà demandé cette personne en ami 😉');
        }
        elseif($friendshipRepository->isAlreadyFriend($this->getUser()->getId(),$userRepository->find($friendId)->getId())){
            return New Response('Bonne nouvelle, vous êtes déjà amis ! 🎉');
        }

       $friend = new Friendship();
       $friend->setUser1($this->getUser());
       $friend->setUser2($userRepository->find($friendId));
       $friend->setAccepted(false);

       $entityManager->persist($friend);
       $entityManager->flush();

       $httpClient->request('GET','https://nathandfd.fr:8080/sendFriendRequest?userId='.$userRepository->find($friendId)->getId().'&friendUsername='.$this->getUser()->getUsername());

        return new Response('Et que votre amitié dure ! 🥰');
    }
}
