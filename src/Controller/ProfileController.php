<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="profile")
     */
    public function index(
        UserRepository $userRepository,
        GameRepository $gameRepository
    ): Response
    {
        $games = $gameRepository->findGameById($this->getUser()->getId());


        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user'=>$this->getUser(),
            'games'=>$games
        ]);
    }
}
