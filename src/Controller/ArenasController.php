<?php
namespace App\Controller;
use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

/**
* Personal Controller
* User personal interface
*
*/
class ArenasController  extends AppController
{

    public function sight()
    {
        $this->loadModel('Fighters');
        $this->loadModel('Surroundings');
        $this->loadModel('Events');
        $width = 15;
	$heigth = 10;
        $trap_detect = 0;
        $monster_detect = 0;

        //check if the player has an active fighter
        $hasAliveFighter = $this->Fighters->hasAliveFighter($this->Auth->user('id'));
        $this->set('hasAliveFighter', $hasAliveFighter);

        if($hasAliveFighter)
        {
			$this->Surroundings->checkToGenerate($width, $heigth);

           	$sightArray = $this->Fighters->getSightArray($this->Auth->user('id'), $width, $heigth);
			$sightArray = $this->Fighters->fillSightArray($this->Auth->user('id'), $sightArray);

	   		$pos = $this->Fighters->getAliveFighter($this->Auth->user('id'), ['coordinate_x', 'coordinate_y']);

            $trap_detect = $this->Surroundings->detect_trap($pos['coordinate_x'],$pos['coordinate_y'],'T');
            $monster_detect = $this->Surroundings->detect_trap($pos['coordinate_x'],$pos['coordinate_y'],'W');


            $this->set([
                    'xmax' => $heigth,
                    'ymax' => $width,
                    'hasAliveFighter' => $this->Fighters->hasAliveFighter($this->Auth->user('id')),
                    'sightArray' => $sightArray,
                    'x' => $pos['coordinate_x'],
                    'y' => $pos['coordinate_y'],
                    'trap_detect' =>$trap_detect,
                    'monster_detect' =>$monster_detect
                    ]);



            if ($this->request->is('post'))
            {
                $x=0;
                $y=0;
                if($this->request->data['dir'] == 'UP')
                {
                    $x=0;
                    $y=(-1);
                }
                if($this->request->data['dir'] == 'DOWN')
                {
                    $x=0;
                    $y=1;
                }
                 if($this->request->data['dir'] == 'RIGHT')
                {
                    $x=1;
                    $y=0;
                }
                if($this->request->data['dir'] == 'LEFT')
                {
                  $x=(-1);
                  $y=0;
                }
                if($this->request->data['attack']==True)
                {
                    $succes_attack=$this->Fighters->attack($this->Auth->user('id'),$x,$y);
                    $this->Flash->success($succes_attack);//0 rien 1 succes 2 parade
                }
                else
                {
                    $this->Fighters->move($this->Auth->user('id'),$x,$y,$sightArray,$heigth,$width);
                }
                $this->redirect(['action'=>'sight']);
            }
        }
    }






	public function fighter()
	{
	 	$this->loadModel('Fighters');

		$list = $this->Fighters->getAllFighters($this->Auth->user('id'));
	 	$this->set([
			'list' => $list,
			'hasAliveFighter' => $this->Fighters->hasAliveFighter($this->Auth->user('id'))
		]);
	}

    public function index()
    {

    }


    public function addFighter()
    {
            $width = 15;
            $heigth = 10;
            $this->loadModel('Fighters');
            $this->loadModel('Surroundings');
            $fighter = $this->Fighters->newEntity();
            $this->set('entity', $fighter);


        if ($this->request->is('post'))
		{
            $fighter = $this->Fighters->patchEntity($fighter, $this->request->getData());

			$fighter->player_id = $this->Auth->user('id');
			do
			{
				$x = rand(0, $width - 1);
				$y = rand(0, $heigth - 1);
				$busy = $this->Surroundings->isEmptySquare($x, $y);
			}
			while($busy);

			$fighter->coordinate_x = $x;
			$fighter->coordinate_y = $y;
			$fighter->skill_health = 5;
			$fighter->current_health = 5;
			$fighter->skill_sight = 2;
			$fighter->skill_strength = 1;
			$fighter->level = 1;
			$fighter->xp = 0;


			if ($this->Fighters->save($fighter)) {
                $this->Flash->success(__('The fighter has been saved.'));

                return $this->redirect(['action' => 'fighter']);
            }
            $this->Flash->error(__('The fighter could not be saved. Please, try again.'));
            }

    }


    public function diary()
    {
  		$this->loadModel('Events');
  		$events = $this->Events->getDayEvents();
      $this->set('events', $events);
    }

    public function guild()
    {
      $res = array();
      $this->loadModel('Fighters');
      $this->loadModel('Guilds');
      $this->loadModel('Messages');
      $guild_id = $this->Fighters->getAliveFighter($this->Auth->user('id'), 'guild_id');
      $fighters = $this->Fighters->getFightersGuild($guild_id['guild_id']);
      foreach($fighters as $fighter)
        {
          $temp = array();
          array_push($temp, $fighter->id);
          array_push($temp, $fighter->name);
          array_push($res, $temp);
        }
      $guild = $this->Guilds->getGuild($guild_id['guild_id']);
      $this->set('guild', $guild);
      $this->set('fighters', $fighters);

    }

    public function addGuild()
    {
      $this->loadModel('Guilds');
      $guild = $this->Guilds->newEntity();


      if ($this->request->is('post')) {
        $guild = $this->Guilds->patchEntity($guild, $this->request->getData());

        if ($this->Guilds->save($guild)) {
            $this->Flash->success(__('The guild has been saved.'));

            return $this->redirect(['action' => 'guild']);
        }
        $this->Flash->error(__('The guild could not be saved. Please, try again.'));
      }
      $this->set('entity', $guild);
    }

    public function findGuilds()
    {
      $this->loadModel('Guilds');
      $this->loadModel('Fighters');
      $res = array();
      $list = $this->Guilds->getAllGuilds();
      foreach($list as $guild):
        $fighters_name = $this->Fighters->getFightersGuild($guild->id);
        $guild['fighters_name'] = $fighters_name;
        array_push($res, $guild);
      endforeach;
      if($this->request->is('post'))
      {
        $gid = $this->request->getData();
        $temp = $this->Fighters->getGuildintoFighter($this->Auth->user('id'));
        if(!$temp['guild_id'] || [$temp['guild_id'] != $gid['guild_id']])
        $this->Fighters->addGuildintoFighter($this->Auth->user('id'), $gid['guild_id']);
        return $this->redirect(['action' => 'guild']);

      }
      $this->set('guilds', $res);

    }

    public function messages($fighter_player_id = null)
    {
      $bool = true;
      $this->loadModel('Messages');
      $this->loadModel('Fighters');
      $entity = $this->Messages->newEntity();
      $fres = array();
      $fid = $this->Fighters->getAliveFighter($this->Auth->user('id'), 'id');
      $fid1 = $this->Fighters->getAliveFighter($fighter_player_id, 'id');
      if(!$fighter_player_id):
        $messages = $this->Messages->find_message($fid['id']);
        foreach($messages as $message):
          $temp =  $this->Fighters->getFighter($message['fighter_id_from'], 'name');
          $message['fighter_name_from'] = $temp[0]['name'];
          $temp = $this->Fighters->getFighter($message['fighter_id'], 'name');
          $message['fighter_name'] = $temp[0]['name'];
        endforeach;
      else:
        $bool = false;
        $messages = $this->Messages->find_message($fid['id'], $fid1['id']);
        foreach($messages as $message):
          $temp =  $this->Fighters->getFighter($message['fighter_id_from'], 'name');
          $message['fighter_name_from'] = $temp[0]['name'];
          $temp = $this->Fighters->getFighter($message['fighter_id'], 'name');
          $message['fighter_name'] = $temp[0]['name'];
        endforeach;
      endif;
      $fighters = $this->Fighters->getEveryFighterAliveExecptOurs($this->Auth->user('id'), ['id', 'name', 'player_id']);
      foreach($fighters as $fighter):
        array_push($fres, $fighter['name']);
      endforeach;
      if($this->request->is('post'))
      {
        if(!$fighter_player_id):
          $req = array();
          $data = $this->request->getData();
          $req['date'] =  Time::now();
          $req['title'] = $data['title'];
          $req['message'] = $data['message'];
          $req['fighter_id_from'] = $fid['id'];
          $req['fighter_id'] = $fighters[$data['fighters_name']]->id;
          if($this->Messages->insert_message($req)):
            $this->Flash->success(__('The message has been saved.'));
             return $this->redirect(['action' => 'messages']);
           else:
              $this->Flash->error(__('The message could not be saved. Please, try again.'));
          endif;

        else:
          $req = array();
          $data = $this->request->getData();
          $req['date'] =  Time::now();
          $req['title'] = $data['title'];
          $req['message'] = $data['message'];
          $req['fighter_id_from'] = $fid['id'];
          $req['fighter_id'] = $fid1['id'];
          if($this->Messages->insert_message($req)):
            $this->Flash->success(__('The message has been saved.'));
             return $this->redirect(['action' => 'messages/'.$fighter_player_id]);
           else:
              $this->Flash->error(__('The message could not be saved. Please, try again.'));
          endif;
        endif;
      }
      $this->set('bool', $bool);
      $this->set('fid', $fid['id']);
      $this->set('fighters', $fres);
      $this->set('fighters_id', $fighters);
      $this->set('messages', $messages);
      $this->set('entity', $entity);
    }


}
