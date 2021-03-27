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
     * @Route("/suce",name="suce")
     */
    public function suce(
        HttpClientInterface $httpClient,
        FriendshipRepository $friendshipRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository
    ): Response
    {
       /* if ($friendshipRepository->isAlreadyFriend($userRepository->find(1),$userRepository->find(7))){
            return New Response('Déjà amis !');
        }
       $friend = new Friendship();
       $friend->setUser1($userRepository->find(7));
       $friend->setUser2($userRepository->find(1));
       $friend->setAccepted(false);

       $entityManager->persist($friend);
       $entityManager->flush();*/

       $httpClient->request('GET','https://nathandfd.fr:8080/sendFriendRequest?userId='.$userRepository->find(1)->getId().'&friendUsername='.$userRepository->find(7)->getUsername());

        return new Response('New friend !');
    }
}
