<?php

namespace App\Events;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JwtCreatedSubscriber
{
    public function updateJwtData(JWTCreatedEvent $event)
    {
        // récup l'utilisateur (pour avoir firstName et lastName)
        $user = $event->getUser();
        $data = $event->getData(); // récup un tableau qui contient toutes les données de base sur l'utilisateur dans le JWT
        $data['firstname'] = $user->getFirstname();
        $data['lastname'] = $user->getLastname();
        $event->setData($data);
    }
}
