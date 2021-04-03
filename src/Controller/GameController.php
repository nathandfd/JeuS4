<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Round;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\GameRepository;
use App\Repository\RoundRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


/**
 * @Route("/game")
 */

class GameController extends AbstractController
{
    /**
     * @Route("/new-game", name="new_game")
     */
    public function newGame(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        RoundRepository $roundRepository,
        GameRepository $gameRepository,
        HttpClientInterface $client
    ): Response {
        $name = $this->getUser();

        $userCriteria = new Criteria();
        $userCriteria->where(Criteria::expr()
            ->andX(Criteria::expr()
                ->eq('ended',null),Criteria::expr()
                ->eq('user1', $name)))
            ->orWhere(Criteria::expr()
                ->andX(Criteria::expr()
                    ->eq('ended',null),Criteria::expr()
                    ->eq('user2', $name)));


        $game = $gameRepository->matching($userCriteria)->first();

        if ($game){
            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()
            ]);
        }

        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->neq('id', $name->getId()));
        $criteria->andWhere(Criteria::expr()->eq('gameReady',1));

        $opponent = $userRepository->matching($criteria)->first();

        if (!$opponent){
            $user = $entityManager->getRepository(User::class)->find($this->getUser());
            $user->setGameReady(true);

            $entityManager->flush();
        }else{

            $client->request('GET', $this->getParameter('app.api_url').'/opponent', [
                'query' => [
                    'userId' => $opponent->getId(),
                    'opponentName' => $name->getFirstName(),
                ],
            ]);

            return $this->redirectToRoute('create_game',['user1_id'=>$name->getId(),'user2_id'=>$opponent->getId()]);
        }

        return $this->render('game/index.html.twig', [
            'user_id' => $this->getUser()->getId(),
        ]);
    }

    /**
     * @Route("/create-game/{user1_id}-{user2_id}", name="create_game")
     */
        public function createGame(
            Request $request,
            EntityManagerInterface $entityManager,
            UserRepository $userRepository,
            CardRepository $cardRepository,
            HttpClientInterface $client,
            $user1_id,$user2_id
        ): Response {
            $user1 = $userRepository->find($user1_id);
            $user2 = $userRepository->find($user2_id);

            $user1->setGameReady(0);
            $user2->setGameReady(0);

            $entityManager->persist($user1);
            $entityManager->persist($user2);

            if ($user1 !== $user2) {
                $game = new Game();
                $game->setUser1($user1);
                $game->setUser2($user2);
                $game->setCreated(new \DateTime('now'));
                $user_array = [
                    $user1,
                    $user2
                ];
                shuffle($user_array);
                $game->setUserTurn($user_array[0]->getId());

                $entityManager->persist($game);

                $set = new Round();
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

                $game->setEnded(new \DateTime('now'));

                $entityManager->persist($set);
                $entityManager->flush();

                $client->request('GET', $this->getParameter('app.api_url').'/game', [
                    'query' => [
                        'userId' => $user1->getId(),
                        'gameId' => $game->getId(),
                    ],
                ]);

                $client->request('GET', $this->getParameter('app.api_url').'/game', [
                    'query' => [
                        'userId' => $user2->getId(),
                        'gameId' => $game->getId(),
                    ],
                ]);

                return $this->redirectToRoute('show_game', [
                    'game' => $game->getId()
                ]);
            } else {
                return $this->redirectToRoute('home');
            }
    }

    /**
     * @Route("/show-game/{game}", name="show_game")
     */
    public function showGame(
        CardRepository $cardRepository,
        Game $game
    ): Response {
        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }
        $user_id = $this->getUser()->getId();

        return $this->render('game/show_game.html.twig', [
            'game' => $game,
            'set' => $game->getRounds()[0],
            'cards' => $tCards,
            'user_id'=>$user_id
        ]);
    }

    /**
     * @Route("/get-tour-game/{game}", name="get_tour")
     */
    public function getTour(
        Game $game
    ): Response {
        if ($this->getUser()->getId() ===  $game->getUserTurn()) {
            return $this->json(true);
        }
        else{
            return $this->json( false);
        }
    }

    /**
     * @param Game $game
     * @route("/refresh/{game}", name="refresh_plateau_game")
     */
    public function refreshPlateauGame(CardRepository $cardRepository, Game $game)
    {
        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser1Action();
            $moi['board'] = $game->getRounds()[0]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser2BoardCards();
        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser2Action();
            $moi['board'] = $game->getRounds()[0]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser1BoardCards();
        } else {
            return new Response('Houston, nous avons un problème ! Un intrus est parmis nous !');
        }



        return $this->render('game/show_game.html.twig', [
            'game' => $game,
            'set' => $game->getRounds()[0],
            'cards' => $tCards,
            'moi' => $moi,
            'adversaire' => $adversaire
        ]);
    }

    /**
     * @Route("/action-game/{game}", name="action_game")
     */
    public function actionGame(
        EntityManagerInterface $entityManager,
        Request $request, Game $game, CardRepository $cardRepository){
        $action = $request->query->get('action');
        $user = $this->getUser();
        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId() && $user->getId() === $game->getUserTurn())
        {
            switch ($action) {
                case 'secret':
                    $carte = $request->query->get('carte');
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $actions['DEPOT'] = [$carte];
                    $actions['OFFRE'] = [$carte];
                    $actions['ECHANGE'] = [$carte];
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    //$supercard = $main[$indexCarte]->getId();
                    //$output->writeln('Secret card :'.$supercard);
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser1HandCards($main);
                    break;
                default:
                    return $this->json(false);
                    break;
            }
            $game->setUserTurn($game->getUser2()->getId());
        } elseif ($game->getUser2()->getId() === $user->getId() && $user->getId() === $game->getUserTurn()) {
            switch ($action) {
                case 'secret':
                    $carte = $request->query->get('carte');
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $actions['DEPOT'] = [$carte];
                    $actions['OFFRE'] = [$carte];
                    $actions['ECHANGE'] = [$carte];
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser2HandCards($main);
                    break;
                default:
                    return $this->json(false);
                    break;
            }
            $game->setUserTurn($game->getUser1()->getId());
        } else {
            return new Response('Houston, nous avons un problème ! Un intrus est parmis nous !');
        }

        $entityManager->flush();

        foreach ($round->getUser1Action() as $action => $value){
            var_dump($value);
            if ($value){
                continue;
            }
            return $this->json(true);
        }

        foreach ($round->getUser2Action() as $action => $value){
            var_dump($value);
            if ($value){
                continue;
            }
            return $this->json(true);
        }

        //Create new set

        $set = new Round();
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

        $set->setBoard($game->getRounds()[0]->getBoard());

        $entityManager->persist($set);
        $entityManager->flush();


        return $this->json(true);
    }
}
