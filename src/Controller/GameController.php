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

            $game->setEnded(new \DateTime('now'));

            $this->newSet($cardRepository, $entityManager, $game);

            $entityManager->refresh($game);
            $round = $game->getRounds()[0];

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
        HttpClientInterface $client,
        Request $request, Game $game, CardRepository $cardRepository){
        $data = json_decode($request->getContent(),true);
        $action = $data['action'];
        $user = $this->getUser();
        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId() && $user->getId() === $game->getUserTurn())
        {
            switch ($action) {
                case 'secret':
                    $carte = $data['card'];
                    $actions = $round->getUser1Action(); //un tableau...
                    if ($actions['SECRET']){
                        return $this->json(false);
                    }
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser1HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    break;
                case 'depot':
                    $cartes[] = $data['card1'];
                    $cartes[] = $data['card2'];
                    $actions = $round->getUser1Action();
                    if ($actions['DEPOT']){
                        return $this->json(false);
                    }
                    $actions['DEPOT'] = $cartes;
                    $round->setUser1Action($actions);
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($data['card1'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card2'], $main);
                    unset($main[$indexCarte]);
                    $round->setUser1HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    break;
                case 'offre':
                    $carte1 = $cardRepository->find($data['card1']);
                    $cartes[] = [
                        'id'=>$carte1->getId(),
                        'picture'=>$carte1->getPicture()
                    ];
                    $carte2 = $cardRepository->find($data['card2']);
                    $cartes[] = [
                        'id'=>$carte2->getId(),
                        'picture'=>$carte2->getPicture()
                    ];
                    $carte3 = $cardRepository->find($data['card3']);
                    $cartes[] = [
                        'id'=>$carte3->getId(),
                        'picture'=>$carte3->getPicture()
                    ];
                    $cartes['done'] = false;
                    $actions = $round->getUser1Action();
                    if ($actions['OFFRE']){
                        return $this->json(false);
                    }
                    $actions['OFFRE'] = $cartes;
                    $round->setUser1Action($actions);
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($data['card1'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card2'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card3'], $main);
                    unset($main[$indexCarte]);
                    $round->setUser1HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser2()->getId(),
                            'cards'=>$cartes
                        ],
                    ]);
                    break;
                case 'echange':
                    $carte1 = $cardRepository->find($data['firstDeck'][0]);
                    $carte2 = $cardRepository->find($data['firstDeck'][1]);
                    $carte3 = $cardRepository->find($data['secondDeck'][0]);
                    $carte4 = $cardRepository->find($data['secondDeck'][1]);
                    $cartes['firstDeck'] = [[
                        'id'=>$carte1->getId(),
                        'picture'=>$carte1->getPicture()
                    ],[
                        'id'=>$carte2->getId(),
                        'picture'=>$carte2->getPicture()
                    ]];
                    $cartes['secondDeck'] = [[
                        'id'=>$carte3->getId(),
                        'picture'=>$carte3->getPicture()
                    ],[
                        'id'=>$carte4->getId(),
                        'picture'=>$carte4->getPicture()
                    ]];
                    $cartes['done'] = false;
                    $actions = $round->getUser1Action();
                    if ($actions['ECHANGE']){
                        return $this->json(false);
                    }
                    $actions['ECHANGE'] = $cartes;
                    $round->setUser1Action($actions);
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($data['firstDeck'][0], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['firstDeck'][1], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['secondDeck'][0], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['secondDeck'][1], $main);
                    unset($main[$indexCarte]);
                    $round->setUser1HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser2()->getId(),
                            'cards'=>$cartes
                        ],
                    ]);
                    break;
                case 'accept_offer':
                    $carte = $data['card'];
                    $actionsAdversaire = $round->getUser2Action();
                    if (!$actionsAdversaire['OFFRE']){
                        return $this->json('Pas cool de tricher petit malin !');
                    }
                    if (!$actionsAdversaire['OFFRE']['done']){
                        $carteIndex = 0;
                        foreach ($actionsAdversaire['OFFRE'] as $key => $value){
                            $carteIndex = array_search($carte, $value);
                            if ($carteIndex){
                                $user1Board = $round->getUser1BoardCards();
                                $user1Board[] = $actionsAdversaire['OFFRE'][$key]['id'];
                                $round->setUser1BoardCards($user1Board);
                                array_splice($actionsAdversaire['OFFRE'],$key,1);
                                $user2Board = $round->getUser2BoardCards();
                                $user2Board[] = $actionsAdversaire['OFFRE'][0]['id'];
                                $user2Board[] = $actionsAdversaire['OFFRE'][1]['id'];
                                $round->setUser2BoardCards($user2Board);
                                $entityManager->flush();
                                break;
                            }
                        }
                        if (!$carteIndex){
                            return $this->json('Pas cool de tricher petit malin !');
                        }
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser1()->getId(),
                            ],
                        ]);
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser2()->getId(),
                            ],
                        ]);
                    }
                    else{
                        return $this->json(false);
                    }
                    break;
                case 'accept_echange':
                    $cartes = $data['cards'];
                    $actionsAdversaire = $round->getUser2Action();
                    if (!$actionsAdversaire['ECHANGE']){
                        return $this->json('Pas cool de tricher petit malin !');
                    }
                    if (!$actionsAdversaire['ECHANGE']['done']) {
                        if ($actionsAdversaire['ECHANGE']['firstDeck'][0]['id'] == $cartes[0] && $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'] == $cartes[1]) {
                            $user2Board = $round->getUser1BoardCards();
                            $user2Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][0]['id'];
                            $user2Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'];
                            $round->setUser1BoardCards($user2Board);
                            $user1Board = $round->getUser2BoardCards();
                            $user1Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][0]['id'];
                            $user1Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'];
                            $round->setUser2BoardCards($user1Board);
                            $entityManager->flush();
                        } elseif ($actionsAdversaire['ECHANGE']['secondDeck'][0]['id'] == $cartes[0] && $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'] == $cartes[1]) {
                            $user1Board = $round->getUser2BoardCards();
                            $user1Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][0]['id'];
                            $user1Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'];
                            $round->setUser2BoardCards($user1Board);
                            $user2Board = $round->getUser1BoardCards();
                            $user2Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][0]['id'];
                            $user2Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'];
                            $round->setUser1BoardCards($user2Board);
                            $entityManager->flush();
                        } else {
                            return $this->json('Pas cool de tricher petit malin !');
                        }
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser1()->getId(),
                            ],
                        ]);
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser2()->getId(),
                            ],
                        ]);
                    }
                        break;
                default:
                    return $this->json(false);
                    break;
            }
            $pioche = $round->getPioche();
            $tirage = array_pop($pioche);
            $user1HandCards = $round->getUser2HandCards();
            $user1HandCards[] = $tirage;
            $round->setUser2HandCards($user1HandCards);
            $round->setPioche($pioche);

            $game->setUserTurn($game->getUser2()->getId());
        } elseif ($game->getUser2()->getId() === $user->getId() && $user->getId() === $game->getUserTurn()) {
            switch ($action) {
                case 'secret':
                    $carte = $data['card'];
                    $actions = $round->getUser2Action(); //un tableau...
                    if ($actions['SECRET']){
                        return $this->json(false);
                    }
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser2HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    break;
                case 'depot':
                    $cartes[] = $data['card1'];
                    $cartes[] = $data['card2'];
                    $actions = $round->getUser2Action();
                    if ($actions['DEPOT']){
                        return $this->json(false);
                    }
                    $actions['DEPOT'] = $cartes;
                    $round->setUser2Action($actions);
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($data['card1'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card2'], $main);
                    unset($main[$indexCarte]);
                    $round->setUser2HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser1()->getId(),
                        ],
                    ]);
                    $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                        'query' => [
                            'userId' => $game->getUser2()->getId(),
                        ],
                    ]);
                    break;
                case 'offre':
                    $carte1 = $cardRepository->find($data['card1']);
                    $cartes[] = [
                        'id'=>$carte1->getId(),
                        'picture'=>$carte1->getPicture()
                    ];
                    $carte2 = $cardRepository->find($data['card2']);
                    $cartes[] = [
                        'id'=>$carte2->getId(),
                        'picture'=>$carte2->getPicture()
                    ];
                    $carte3 = $cardRepository->find($data['card3']);
                    $cartes[] = [
                        'id'=>$carte3->getId(),
                        'picture'=>$carte3->getPicture()
                    ];
                    $cartes['done'] = false;
                    $actions = $round->getUser2Action();
                    if ($actions['OFFRE']){
                        return $this->json(false);
                    }
                    $actions['OFFRE'] = $cartes;
                    $round->setUser2Action($actions);
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($data['card1'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card2'], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['card3'], $main);
                    unset($main[$indexCarte]);
                    $round->setUser2HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser1()->getId(),
                            'cards'=>$cartes
                        ],
                    ]);
                    break;
                case 'echange':
                    $carte1 = $cardRepository->find($data['firstDeck'][0]);
                    $carte2 = $cardRepository->find($data['firstDeck'][1]);
                    $carte3 = $cardRepository->find($data['secondDeck'][0]);
                    $carte4 = $cardRepository->find($data['secondDeck'][1]);
                    $cartes['firstDeck'] = [[
                        'id'=>$carte1->getId(),
                        'picture'=>$carte1->getPicture()
                    ],[
                        'id'=>$carte2->getId(),
                        'picture'=>$carte2->getPicture()
                    ]];
                    $cartes['secondDeck'] = [[
                        'id'=>$carte3->getId(),
                        'picture'=>$carte3->getPicture()
                    ],[
                        'id'=>$carte4->getId(),
                        'picture'=>$carte4->getPicture()
                    ]];
                    $cartes['done'] = false;
                    $actions = $round->getUser2Action();
                    if ($actions['ECHANGE']){
                        return $this->json(false);
                    }
                    $actions['ECHANGE'] = $cartes;
                    $round->setUser2Action($actions);
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($data['firstDeck'][0], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['firstDeck'][1], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['secondDeck'][0], $main);
                    unset($main[$indexCarte]);
                    $indexCarte = array_search($data['secondDeck'][1], $main);
                    unset($main[$indexCarte]);
                    $round->setUser2HandCards($main);
                    $client->request('POST', $this->getParameter('app.api_url').'/action/'.$action, [
                        'body' => [
                            'userId' => $game->getUser1()->getId(),
                            'cards'=>$cartes
                        ],
                    ]);
                    break;
                case 'accept_offer':
                    $carte = $data['card'];
                    $actionsAdversaire = $round->getUser1Action();
                    if (!$actionsAdversaire['OFFRE']){
                        return $this->json('Pas cool de tricher petit malin !');
                    }
                    if (!$actionsAdversaire['OFFRE']['done']){
                        $carteIndex = 0;
                        foreach ($actionsAdversaire['OFFRE'] as $key => $value){
                            $carteIndex = array_search($carte, $value);
                            if ($carteIndex){
                                $user1Board = $round->getUser2BoardCards();
                                $user1Board[] = $actionsAdversaire['OFFRE'][$key]['id'];
                                $round->setUser2BoardCards($user1Board);
                                array_splice($actionsAdversaire['OFFRE'],$key,1);
                                $user2Board = $round->getUser1BoardCards();
                                $user2Board[] = $actionsAdversaire['OFFRE'][0]['id'];
                                $user2Board[] = $actionsAdversaire['OFFRE'][1]['id'];
                                $round->setUser1BoardCards($user2Board);
                                $entityManager->flush();
                                return $this->json($user2Board);
                                break;
                            }
                        }
                        if (!$carteIndex){
                            return $this->json('Pas cool de tricher petit malin !');
                        }
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser1()->getId(),
                            ],
                        ]);
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser2()->getId(),
                            ],
                        ]);
                    }
                    else{
                        return $this->json(false);
                    }
                    break;
                case 'accept_echange':
                    $cartes = $data['cards'];
                    $actionsAdversaire = $round->getUser1Action();
                    if (!$actionsAdversaire['ECHANGE']){
                        return $this->json('Pas cool de tricher petit malin !');
                    }
                    if (!$actionsAdversaire['ECHANGE']['done']){
                        if ($actionsAdversaire['ECHANGE']['firstDeck'][0]['id'] == $cartes[0] && $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'] == $cartes[1]){
                            $user2Board = $round->getUser2BoardCards();
                            $user2Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][0]['id'];
                            $user2Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'];
                            $round->setUser2BoardCards($user2Board);
                            $user1Board = $round->getUser1BoardCards();
                            $user1Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][0]['id'];
                            $user1Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'];
                            $round->setUser1BoardCards($user1Board);
                            $entityManager->flush();
                        }
                        elseif ($actionsAdversaire['ECHANGE']['secondDeck'][0]['id'] == $cartes[0] && $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'] == $cartes[1]){
                            $user1Board = $round->getUser1BoardCards();
                            $user1Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][0]['id'];
                            $user1Board[] = $actionsAdversaire['ECHANGE']['firstDeck'][1]['id'];
                            $round->setUser1BoardCards($user1Board);
                            $user2Board = $round->getUser2BoardCards();
                            $user2Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][0]['id'];
                            $user2Board[] = $actionsAdversaire['ECHANGE']['secondDeck'][1]['id'];
                            $round->setUser2BoardCards($user2Board);
                            $entityManager->flush();
                        }
                        else{
                            return $this->json('Pas cool de tricher petit malin !');
                        }

//                            $carteIndex = array_search($carte, $value);
//                            $carteIndex2 = array_search($carte, $value);
//                            if ($carteIndex){
//                                $user1Board = $round->getUser2BoardCards();
//                                $user1Board[] = $actionsAdversaire['OFFRE'][$key]['id'];
//                                $round->setUser2BoardCards($user1Board);
//                                array_splice($actionsAdversaire['OFFRE'],$key,1);
//                                $user2Board = $round->getUser1BoardCards();
//                                $user2Board[] = $actionsAdversaire['OFFRE'][0]['id'];
//                                $user2Board[] = $actionsAdversaire['OFFRE'][1]['id'];
//                                $round->setUser1BoardCards($user2Board);
//                                $entityManager->flush();
//                                return $this->json($user2Board);
//                                break;
//                            }
//                        }
//                        if (!$carteIndex){
//                            return $this->json('Pas cool de tricher petit malin !');
//                        }
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser1()->getId(),
                            ],
                        ]);
                        $client->request('GET', $this->getParameter('app.api_url').'/reload', [
                            'query' => [
                                'userId' => $game->getUser2()->getId(),
                            ],
                        ]);

                    }
                    else{
                        return $this->json(false);
                    }
                    break;
                default:
                    return $this->json(false);
                    break;
            }
            $pioche = $round->getPioche();
            $tirage = array_pop($pioche);
            $user1HandCards = $round->getUser1HandCards();
            $user1HandCards[] = $tirage;
            $round->setUser1HandCards($user1HandCards);
            $round->setPioche($pioche);
            $game->setUserTurn($game->getUser1()->getId());
        } else {
            return new Response('Houston, nous avons un problème ! Un intrus est parmis nous !');
        }

        $entityManager->flush();

        foreach ($round->getUser1Action() as $action => $value){
            if ($value){
                continue;
            }
            return $this->json(true);
        }

        foreach ($round->getUser2Action() as $action => $value){
            if ($value){
                continue;
            }
            return $this->json(true);
        }

        //TODO "Envoyer fin de partie aux joueurs et rediriger"
        $user1_cards = $round->getUser1BoardCards();
        $user2_cards = $round->getUser2BoardCards();
        $board = $round->getBoard();
        $user1_geishas = [];
        $user2_geishas = [];
        $user1_points = 0;
        $user2_points = 0;
        $user1_nb_geisha = 0;
        $user2_nb_geisha = 0;

        foreach ($board as $object => $value){
            $user1_geishas[$object] = 0;
            $user2_geishas[$object] = 0;

            foreach ($user1_cards as $card_key => $card){
                $full_card = $cardRepository->find($card);
                if ($full_card->getName() === $object){
                    if($user1_geishas[$full_card->getName()]){
                        $user1_points += $full_card->getNumber();
                    }
                    $user1_geishas[$full_card->getName()] =+ 1;
                }
            }

            foreach ($user2_cards as $card_key => $card){
                $full_card = $cardRepository->find($card);
                if ($full_card->getName() === $object){
                    if($user2_geishas[$full_card->getName()]){
                        $user2_points += $full_card->getNumber();
                    }
                    $user2_geishas[$full_card->getName()] =+ 1;
                }
            }

            if ($user1_geishas[$object] > $user2_geishas[$object]){
                $board[$object] = $game->getUser1()->getId();
                $user1_nb_geisha += 1;
            }
            elseif ($user2_geishas[$object] > $user1_geishas[$object]){
                $board[$object] = $game->getUser2()->getId();
                $user2_nb_geisha += 1;
            }
        }

        if ($user1_nb_geisha >= 4){
            $game->setWinner($game->getUser1());
        }elseif ($user2_nb_geisha >= 4){
            $game->setWinner($game->getUser2());
        }

        if ($user1_points >= 11){
            $game->setWinner($game->getUser1());
        }elseif ($user2_points >= 11){
            $game->setWinner($game->getUser2());
        }

        if ($game->getRounds()[2]){
            if ($round->getUser1Points() > $round->getUser2Points()){
                $game->setWinner($game->getUser1());
            }
            elseif ($round->getUser2Points() >$round->getUser1Points()){
                $game->setWinner($game->getUser2());
            }
        }

        $round->setBoard($board);
        $round->setUser1Points($round->getUser1Points() + $user1_points);
        $round->setUser2Points($round->getUser2Points() + $user2_points);

        $entityManager->flush();

        if (!$game->getWinner()){
            $this->newSet($cardRepository, $entityManager, $game);
        }
        else{
            return $this->json('Partie terminée Blyat !');
        }

        return $this->json(true);
    }

    private function newSet(CardRepository $cardRepository, EntityManagerInterface $entityManager,Game $game){
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

        if ($game->getRounds()[0]){
            $set->setBoard($game->getRounds()[0]->getBoard());
            $set->setUser1Points($game->getRounds()[0]->getUser1Points());
            $set->setUser2Points($game->getRounds()[0]->getUser1Points());
        }
        else{
            $set->setBoard([
                'pistolet' => 'N',
                'lampe' => 'N',
                'oreillette' => 'N',
                'ordinateur' => 'N',
                'fiole' => 'N',
                'couteau' => 'N',
                'cigarettes' => 'N'
        ]);
            $set->setUser1Points(0);
            $set->setUser2Points(0);
        }

        if ($game->getUserTurn() == $game->getUser1()->getId()){
            $pioche = $set->getPioche();
            $tirage = array_pop($pioche);
            $user1HandCards = $set->getUser1HandCards();
            $user1HandCards[] = $tirage;
            $set->setUser1HandCards($user1HandCards);
            $set->setPioche($pioche);
        }
        else{
            $pioche = $set->getPioche();
            $tirage = array_pop($pioche);
            $user1HandCards = $set->getUser2HandCards();
            $user1HandCards[] = $tirage;
            $set->setUser2HandCards($user1HandCards);
            $set->setPioche($pioche);
        }

        $entityManager->persist($set);
        $entityManager->flush();
    }
}
