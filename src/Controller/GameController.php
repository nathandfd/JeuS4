<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Set;
use App\Repository\CardRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    /**
     * @Route("/new-game", name="new_game")
     */
    public function newGame(
        UserRepository $userRepository
    ): Response {
        $users = $userRepository->findAll();

        return $this->render('game/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/create-game", name="create_game")
     */
    public function createGame(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CardRepository $cardRepository
    ): Response {
        $user1 = $userRepository->find($request->request->get('user1'));
        $user2 = $userRepository->find($request->request->get('user2'));

        if ($user1 !== $user2) {
            $game = new Game();
            $game->setUser1($user1);
            $game->setUser2($user2);
            $game->setCreated(new \DateTime('now'));

            $entityManager->persist($game);

            $set = new Set();
            $set->setGame($game);
            $set->setCreated(new \DateTime('now'));
            $set->setSetNumber(1);

            $cards = $cardRepository->findAll();
            $tCards = [];
            foreach ($cards as $card) {
                $tCards[$card->getId()] = $card;
            }
            shuffle($tCards);
            $carte = array_pop($tCards);
            $set->setRemovedCard($carte->getId());

            $tMainJ1 = [];
            $tMainJ2 = [];
            for ($i = 0; $i < 6; $i++) {
                //on distribue 6 cartes aux deux joueurs
                $carte = array_pop($tCards);
                $tMainJ1[] = $carte->getId();
                $carte = array_pop($tCards);
                $tMainJ2[] = $carte->getId();
            }
            $set->setUser1HandCards($tMainJ1);
            $set->setUser2HandCards($tMainJ2);

            $tPioche = [];

            foreach ($tCards as $card) {
                $carte = array_pop($tCards);
                $tPioche[] = $carte->getId();
            }
            $set->setPioche($tPioche);
            $set->setUser1Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setUser2Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setBoard([
                'EMPL1' => ['N'],
                'EMPL2' => ['N'],
                'EMPL3' => ['N'],
                'EMPL4' => ['N'],
                'EMPL5' => ['N'],
                'EMPL6' => ['N'],
                'EMPL7' => ['N']
            ]);
            $entityManager->persist($set);
            $entityManager->flush();
        } else {
            return $this->redirectToRoute('new_game');
        }
    }
}
