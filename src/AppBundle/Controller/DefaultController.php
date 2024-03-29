<?php

namespace AppBundle\Controller;

use AppBundle\Tic\Game;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        return self::startAction();
    }

    public function startAction()
    {
        $gameModel = $this->get('app.model.game');
        $gameModel->startGame();
        $game = $gameModel->getGame();
        $score = $gameModel->getScore();

        return $this->render(
            'AppBundle:Default:start.html.twig', array(
            'grid' => $game->getBoard()->getGrid(),
            'currentPlayer' => $game->getCurrentPlayer(),
            'gameState' => $game->getState(),
            'winningGrid' => array(),
            'score' => $score,
            'playerMode' => $gameModel->getPlayerMode(),
        ));
    }

    public function playAction($row, $col)
    {
        $messages = array();
        $gameModel = $this->get('app.model.game');
        $game = $gameModel->getGame();
        $score = $gameModel->getScore();
        if(!$this->isGameOver($game))
        {
            if(!$game->isMoveLegal($row, $col)) {
                $messages []= 'illegal move';
            } else {
                $game->makeMove($row, $col);

                /**
                 * If player didn't choose to play  a game of two
                 */
                if($this->get('app.model.game')->getPlayerMode() == 1){
                    /**
                     * Make a pc move
                     * @todo extract to separate method
                     */
                    if (!$this->isGameOver($game) && !$game->getBoard()->isFull()){
                        do{
                            $randomRow = mt_rand(0,2);
                            $randomCol = mt_rand(0,2);
                        }
                        while(!$game->isMoveLegal($randomRow, $randomCol));
                        $game->makeMove($randomRow, $randomCol);
                    }

                }
                $this->get('app.model.game')->setGame($game);
            }
            if ($this->isGameOver($game)){
                return $this->redirectToRoute('end');
            }
        }
        else {
            return $this->redirectToRoute('end');
        }

        return $this->render(
            'AppBundle:Default:play.html.twig', array(
            'row' => $row,
            'col' => $col,
            'messages' => $messages,
            'grid' => $game->getBoard()->getGrid(),
            'currentPlayer' => $game->getCurrentPlayer(),
            'gameState' => $game->getState(),
            'winningGrid' => array(),
            'score' => $score,
            'playerMode' => $gameModel->getPlayerMode(),
        ));
    }

    public function endAction()
    {
        $message = '';
        $gameModel = $this->get('app.model.game');
        $game = $gameModel->getGame();
        $gameState = $game->getState();
        $grid = $game->getBoard()->getGrid();
        $winningGrid = false;
        $game->updateScore($gameModel);
        $score = $gameModel->getScore();


        if(Game::STATE_TIE == $gameState) {
            $message = 'Game Over: tie! how boring!';
        } else {
            $message = 'Game Over: ' . $game->getWinner() . ' has won!';
            $winningGrid = $game->getBoard()->getWinningGrid();
        }


        return $this->render(
            'AppBundle:Default:end.html.twig', array(
            'message' => $message,
            'grid' => $grid,
            'gameState' => $gameState,
            'winningGrid' => $winningGrid,
            'score' => $score,
            'playerMode' => $gameModel->getPlayerMode(),
        ));
    }

    private function isGameOver(Game $game)
    {
        return in_array($game->getState(), array(Game::STATE_TIE, Game::STATE_WON));
    }

    /**
     * Change player : pc or human and redirect back to start page
     * @param $mode
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changePlayerModeAction($mode)
    {
        $this->get('app.model.game')->changePlayerMode($mode);
        return $this->redirectToRoute('start');
    }
}
