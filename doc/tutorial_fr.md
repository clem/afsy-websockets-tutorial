# Symfony et WebSockets

Nous allons voir aujourd’hui comment mettre en place facilement et rapidement un Chat dans votre projet Symfony. 
Mais avant de commencer ce tutoriel, reprenons un peu quelques bases.

![Cat on computer](https://media.giphy.com/media/130OHeDvqdHiy4/giphy.gif)

## Introduction

Dans un site web traditionnel, lorsqu’on veut faire des échanges entre la page web qu’un utilisateur est entrain 
de consulter et le serveur, le tout sans recharger la page,  on fait une requête en AJAX. 
C’est un procédé assez simple à mettre en place mais qui a un gros inconvénient : 
la communication entre le « client » et le serveur ne se fait que dans un sens. 
Le client fait une requête au serveur, qui lui répond en lui apportant (plus ou moins) ce qu’il a demandé. 
Le problème, c’est que dans certains cas, la demande du client peut nécessiter du temps. 
Dans ce cas là, ce client est obligé de faire des requêtes régulières au serveur pour lui demander où en est sa demande.
Évidemment, ça ne fait plaisir ni au client, ni au serveur. 

C’est là que les WebSockets interviennent. Ils permettent, par l’intermédiaire d’un serveur supplémentaire, 
la création d’un système de notifications et l’envoi de messages du client au serveur… et vice versa !

## Installation de Symfony et de Ratchet

Pour réaliser ce tutoriel, nous allons partir d’une version de **Symfony 3.4**, avec la librairie **Ratchet**. 
Ratchet est une librairie PHP qui facilite la mise en place d’applications utilisant des WebSockets 
et qui va nous faire gagner pas mal de temps (cf : 
[l’article de Raphaël Gonçalves](http://www.raphael-goncalves.fr/blog/chat-temps-reel-en-php-avec-des-websocket)).

A noter : J’aurai préféré utiliser Symfony 4 (fraichement sorti), mais, à l’heure où j’écris ce tutoriel, 
la librairie `cboden/ratchet` n’est pas encore compatible avec Symfony 4 
([ça ne saurait tarder](https://github.com/ratchetphp/Ratchet/pull/579)).

```bash
$ symfony new afsy-websocket-tutorial 3.4
$ cd afsy-websocket-tutorial
$ composer require cboden/ratchet
```

## Création de notre serveur de Chat

### Création du serveur

Pour créer notre serveur de chat, nous allons avoir besoin d’une seule chose : 
une classe qui implémente l’interface `Ratchet\MessageComponentInterface`. 
Nous créons donc le fichier `src/AppBundle/Server/Chat.php` avec le contenu suivant :

```php
// src/AppBundle/Server/Chat.php
namespace AppBundle\Server;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface
{
    private $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $conn->send(sprintf('New connection: Hello #%d', $conn->resourceId));
    }

    public function onClose(ConnectionInterface $closedConnection)
    {
        $this->clients->detach($closedConnection);
        echo sprintf('Connection #%d has disconnected\n', $closedConnection->resourceId);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send('An error has occurred: '.$e->getMessage());
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        $totalClients = count($this->clients) - 1;
        echo vsprintf(
            'Connection #%1$d sending message "%2$s" to %3$d other connection%4$s'."\n", [
            $from->resourceId,
            $message,
            $totalClients,
            $totalClients === 1 ? '' : 's'
        ]);
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($message);
            }
        }
    }
}
```

### Création du script de lancement du serveur de Chat

Et voilà, c’est tout… Ou presque. Il faut maintenant le lancer. Pour ça, 2 possibilités s’offrent à nous : 

- faire un script PHP qui charge cette classe
- ou créer une commande Symfony

Evidemment, nous allons créer une commande Symfony : ça semble plus cohérent et, accessoirement, 
ça nous permet de rester dans le thème.

```php
// src/AppBundle/Command/ChatServerCommand.php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ratchet\Server\IoServer;
use AppBundle\Server\Chat;

class ChatServerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('afsy:app:chat-server')
            ->setDescription('Start chat server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = IoServer::factory(
            new Chat(),
            8080,
            '127.0.0.1'
        );
        $server->run();
    }
}
```

Nous pourrions mettre le port `8080` en paramètre de l’application, mais nous allons nous concentrer sur l’essentiel.

### Lancement du serveur de chat

![Ninja cat](https://media.giphy.com/media/WdkpFbqU4KPyE/giphy.gif)

Une fois la classe et la commande créées, il ne reste plus qu’à lancer la commande et à la tester :

```bash
$ php bin/console afsy:app:chat-server
```

Et là, il ne se passe (presque) rien ! Tout est normal. Pour tester que le script fonctionne bien, 
il faut lancer une connexion à ce serveur (depuis une autre fenêtre de terminal) :

```bash
$ telnet 127.0.0.1 8080
# Et vous devriez avoir un message qui ressemble à :
# Trying 127.0.0.1...
# Connected to localhost.
# Escape character is '^]'.
# New connection: Hello #545
```

Vous pouvez évidemment ouvrir plusieurs fenêtres comme celle-là et tester que les messages envoyés 
par une des fenêtres sont bien envoyés aux autres fenêtres.

## Mise en place du WebSocket

Maintenant que nous avons vu que le serveur de chat était opérationnel, nous allons pouvoir créer notre page de chat. 
Pour cela, nous allons seulement modifier la page par défaut de Symfony pour y ajouter notre code.

### Création de la page du chat

Nous allons tout d’abord modifier le contrôleur pour remplacer le contenu de l’action `indexAction` par :

```php
// src/AppBundle/Controller/DefaultController.php
return $this->render('default/index.html.twig', [
    'ws_url' => 'localhost:8080',
]);
```

Ainsi que le contenu de la vue associée par le contenu suivant :

```twig
{# app/Resources/views/default/index.html.twig #}
{% extends 'base.html.twig' %}
{% block body %}
    <div class="container">
      <h1>AFSY - WebSockets and Symfony</h1>
      <div id="ws-content-receiver">
        Connecting...
      </div>
    </div>
{% endblock %}
{% block javascripts %}
  <script type="text/javascript">
    var wsUrl = '{{ ws_url }}';
  </script>
  <script type="text/javascript" src="{{ asset('bundles/app/js/sf-websocket.js') }}"></script>
{% endblock %}
```

Nous sommes fin prêts à mettre en place le JavaScript dans le fichier `js/sf-websocket.js`. 
Petit conseil avant de passer à la suite : il est grandement conseillé de mettre en place [ESLint](https://eslint.org) 
sur le projet (afin d’éviter de nombreuses erreurs et d’avoir une base de code uniforme).

### Mise en place du JavaScript et du WebSocket

Avant de mettre en place le JavaScript, il est important de modifier la commande du serveur. 
Celle que nous avons créée ne supportait que les appels de la ligne de commande et il faut modifier 
le premier paramètre du constructeur de `IoServer::factory()` pour pouvoir ajouter un support en HTTP et en WebSocket.

Notre serveur se lancera donc de la manière suivante à partir de maintenant :

```php
// src/AppBundle/Command/ChatServerCommand.php
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(new WsServer(new Chat())),
    8080,
    '127.0.0.1'
);
```

Nous allons maintenant modifier notre fichier JavaScript pour y ajouter le contenu suivant :

```JavaScript
// src/AppBundle/Resources/public/js/sf-websocket.js
/* globals wsUrl: true */
(function () {
  'use strict';

  var _receiver = document.getElementById('ws-content-receiver');
  var ws = new WebSocket('ws://' + wsUrl);

  ws.onopen = function () {
    ws.send('Hello');
    _receiver.innerHTML = 'Connected !';
  };

  ws.onmessage = function (event) {
    _receiver.innerHTML = event.data;
  };

  ws.onclose = function () {
    _receiver.innerHTML = 'Connection closed';
  };

  ws.onerror = function () {
    _receiver.innerHTML = 'An error occured!';
  };
})();
```

### Premiers tests

Pour faire notre premier test, nous avons besoin de :

- générer les assets, pour que le fichier JavaScript soit pris en compte : `php bin/console assets:install --symlink`
- lancer le serveur Symfony, pour que la page soit accessible : `php bin/console server:start`
- lancer notre serveur de chat : `php bin/console afsy:app:chat-server`
- et c’est déjà pas mal !

Il ne nous reste plus qu’à aller sur la page que nous venons de modifier pour voir le résultat suivant :

[image:images/premiers-tests.png]

Ça y est, nous avons une connexion en WebSocket entre notre JavaScript et notre application Symfony.

## Création du Chat

Maintenant que « le plus dur » est fait, nous allons créer un chat et vérifier que tout communique bien ensemble.

### PIMP my Chat

Pour ajouter la notion d’utilisateur et de canal, nous allons modifier notre serveur de chat 
pour lui ajouter 3 propriétés :

- `users` qui sera la liste des utilisateurs : une liste associative avec la clé qui correspond à l’id de la connexion 
  et dont la valeur est un tableau associatif contenant l’object de connexion, le pseudo de l’utilisateur 
  et la liste des canaux auxquels il est abonné
- `botName` qui sera notre utilisateur par défaut
- `defaultChannel` qui sera le nom du canal par défaut

```php
// src/AppBundle/Server/Chat.php
private $users = [];
private $botName = 'ChatBot';
private $defaultChannel = 'general';
```

Une fois nos variables ajoutées, on modifie la méthode `onOpen()` pour ajouter la connexion à 
la liste des utilisateurs :

```php
// src/AppBundle/Server/Chat.php
$this->users[$conn->resourceId] = [
    'connection' => $conn,
    'user' => '',
    'channels' => []
];
```

Bien évidemment, on modifie aussi la méthode `onClose()` pour supprimer l’utilisateur de la liste 
lors de sa déconnexion :

```php
// src/AppBundle/Server/Chat.php
unset($this->users[$closedConnection->resourceId]);
```

Maintenant que nous avons un système qui peut gérer des utilisateurs et des canaux, 
nous allons modifier la communication entre le front et le serveur de chat pour tout passer en JSON. 

Nous avons utilisé des messages simples au début, mais pour avoir un chat digne de ce nom, 
le JSON semble une des solutions les plus simples et efficaces à mettre en place.

Nous allons tout d’abord modifier la réception des messages dans la méthode `Chat::onMessage()` 
pour gérer plusieurs actions :

- `subscribe` : pour lier un utilisateur à un canal
- `unsubscribe` : pour supprimer le lien entre l'utilisateur et le canal
- `message` : pour que l'utilisateur puisse poster un message

```php 
// src/AppBundle/Server/Chat.php
public function onMessage(ConnectionInterface $conn, $message)
{
    $messageData = json_decode($message);
    if ($messageData === null) {
        return false;
    }

    $action = $messageData->action ?? 'unknown';
    $channel = $messageData->channel ?? $this->defaultChannel;
    $user = $messageData->user ?? $this->botName;
    $message = $messageData->message ?? '';

    switch ($action) {
        case 'subscribe':
            $this->subscribeToChannel($conn, $channel, $user);
            return true;
        case 'unsubscribe':
            $this->unsubscribeFromChannel($conn, $channel, $user);
            return true;
        case 'message':
            return $this->sendMessageToChannel($conn, $channel, $user, $message);
        default:
            echo sprintf('Action "%s" is not supported yet!', $action);
            break;
    }
    return false;
}
```

Comme vous pouvez le voir, ces actions auront, pour plus de lisibilité et de maintenabilité, 
chacune leur propre méthode :

```php
// src/AppBundle/Server/Chat.php
private function subscribeToChannel(ConnectionInterface $conn, $channel, $user)
{
    $this->users[$conn->resourceId]['channels'][$channel] = $channel;
    $this->sendMessageToChannel(
        $conn,
        $channel,
        $this->botName,
        $user.' joined #'.$channel
    );
}
```

```php
// src/AppBundle/Server/Chat.php
private function unsubscribeFromChannel(ConnectionInterface $conn, $channel, $user)
{
    if (array_key_exists($channel, $this->users[$conn->resourceId]['channels'])) {
        unset($this->users[$conn->resourceId]['channels']);
    }
    $this->sendMessageToChannel(
        $conn,
        $channel,
        $this->botName,
        $user.' left #'.$channel
    );
}
```

```php
// src/AppBundle/Server/Chat.php
private function sendMessageToChannel(ConnectionInterface $conn, $channel, $user, $message)
{
    if (!isset($this->users[$conn->resourceId]['channels'][$channel])) {
        return false;
    }
   foreach ($this->users as $connectionId => $userConnection) {
        if (array_key_exists($channel, $userConnection['channels'])) {
            $userConnection['connection']->send(json_encode([
                'action' => 'message',
                'channel' => $channel,
                'user' => $user,
                'message' => $message
            ]));
        }
    }
    return true;
}
```

Voilà notre serveur de chat peut maintenant recevoir et gérer différents utilisateurs, canaux et types d’actions.

### Back to the Front

Nous allons passer maintenant à la mise à jour du front, pour qu’il puisse envoyer des messages dignes de ce nom 
au serveur. Pour ça, nous allons modifier le template pour ajouter un formulaire d’envoi de message :

```Twig 
{# app/Resources/views/default/index.html.twig #}
<div id="ws-content-receiver"></div>
<input type="text" id="ws-content-to-send" />
<button id="ws-send-content">Send</button>
```

Le block `#ws-content-receiver` a été vidé pour qu’il soit entièrement géré par JavaScript. 
Vous noterez que le formulaire est très basique et qu’il ne contient même pas la balise `<form>`, 
parce que ça n’est pas vraiment un « vrai » formulaire. Une fois cette modification faite, 
nous allons revenir sur notre fichier JavaScript pour y ajouter plusieurs choses :

- mettre à jour le système d’envoi de messages au Back
- ajouter une méthode d’affichage de message dans le HTML
- ajouter une méthode pour que le ChatBot puisse communiquer avec l’utilisateur
- demander à l’utilisateur son nom quand il arrive sur la page
- envoyer de messages de l’utilisateur au serveur

#### Mise à jour du système d’envoi et d’affichage des messages

Dorénavant, nous devons envoyer des messages au serveur au format JSON et nous allons donc adapter les méthodes du 
WebSocket pour prendre en compte cette modification. Au passage, nous ajoutons (comme nous l’avons fait côté serveur) 
un canal et un nom de bot par défaut.

```JavaScript
// src/AppBundle/Resources/public/js/sf-websocket.js
var defaultChannel = 'general';
var botName = 'ChatBot';

var addMessageToChannel = function(message) {
  _receiver.innerHTML += '<div class="message">' + message + '</div>';
};

var botMessageToGeneral = function (message) {
  return addMessageToChannel(JSON.stringify({
    action: 'message',
    channel: defaultChannel,
    user: botName,
    message: message
  }));
};

ws.onopen = function () {
  ws.send(JSON.stringify({
    action: 'subscribe',
    channel: defaultChannel,
    user: userName
  }));
};

ws.onmessage = function (event) {
  addMessageToChannel(event.data);
};

ws.onclose = function () {
  botMessageToGeneral('Connection closed');
};

ws.onerror = function () {
  botMessageToGeneral('An error occured!');
};
```

#### Papiers s’il vous plaît

Il nous faut maintenant demander à l’utilisateur son identifiant et nous allons le faire de manière très simpliste :

```JavaScript
// src/AppBundle/Resources/public/js/sf-websocket.js
var userName = prompt('Hi! I need your name for the Chat please :)');
```

Et c’est tout. 

Dans une « vraie » application, il faudrait vérifier si un autre utilisateur n’a pas le même identifiant, 
mais pour ce tutoriel, nous allons faire simple.

#### I'll send an SOS to the world

Pour envoyer un message de l’utilisateur, il nous faut 2 choses :

- une méthode d’envoi du contenu présent dans le champs texte et qui ré-initialise son contenu une fois envoyé
- un lien entre cette méthode et le clic sur le bouton « Send »
- et le petit bonus : un lien entre la touche entrée et la méthode d’envoi pour envoyer automatiquement le message 
  après avoir appuyé sur cette touche

```JavaScript
// src/AppBundle/Resources/public/js/sf-websocket.js
var _textInput = document.getElementById('ws-content-to-send');
var _textSender = document.getElementById('ws-send-content');
var enterKeyCode = 13;

var sendTextInputContent = function () {
  // Get text input content
  var content = _textInput.value;

  // Send it to WS
  ws.send(JSON.stringify({
    action: 'message',
    user: userName,
    message: content,
    channel: 'general'
  }));

  // Reset input
  _textInput.value = '';
};

_textSender.onclick = sendTextInputContent;
_textInput.onkeyup = function(e) {
  // Check for Enter key
  if (e.keyCode === enterKeyCode) {
    sendTextInputContent();
  }
};
```

On peut maintenant relancer notre serveur de chat et retourner sur notre page pour vérifier que tout fonctionne bien.

[image:images/afsy-prompt.png]

Le navigateur nous demande bien notre identité au chargement de la page.

[image:images/test-json.png]

Et nous avons bien une communication qui se fait en JSON. Vous pouvez normalement envoyer des messages 
qui s’afficheront dans l’ensemble des fenêtres connectées à l’application.

[image:images/tests-json-messages.png]

## Le mot de la fin

Et voilà, c’est terminé pour aujourd’hui ! Alors oui, l’interface est minimaliste et tout reste à faire, 
mais le but de cet article était de vous montrer comment mettre en place des WebSockets dans un projet Symfony. 
Pour aller plus loin, j’ai créé un [projet Github](https://github.com/clem/afsy-websockets-tutorial) 
avec le code commenté (contrairement à celui publié dans cet article). 

J’en ai profité aussi pour mettre à disposition [une démo](https://afsy-chat.herokuapp.com/) 
avec une interface un peu moins rebutante.

[image:images/main-content.png]

J’espère que cet article vous aura plu, je vous remercie de l’avoir lu jusqu’au bout, 
et il ne me reste plus qu’à vous souhaiter de belles fêtes de fin d’année !
