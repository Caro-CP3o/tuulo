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
        $data['firstName'] = $user->getFirstName();
        $data['lastName'] = $user->getLastName();
        $event->setData($data);
    }
}
