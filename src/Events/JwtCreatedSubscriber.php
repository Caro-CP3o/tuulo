<?php

namespace App\Events;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JwtCreatedSubscriber
{
    /**
     * This method listens to the JWTCreatedEvent and adds custom user data
     * (first name, last name, roles) into the JWT payload.
     *
     * @param \Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent $event
     * @return void
     */
    public function updateJwtData(JWTCreatedEvent $event)
    {
        // ---------------------------
        // Retrieve the authenticated User object from the event
        // ---------------------------
        $user = $event->getUser();
        // ---------------------------
        // Get the current payload data that will be encoded into the JWT
        // ---------------------------
        $data = $event->getData();
        // ---------------------------
        // Add extra user data to the JWT payload
        // ---------------------------
        $data['firstName'] = $user->getFirstName();
        $data['lastName'] = $user->getLastName();
        $data['roles'] = $user->getRoles();
        // ---------------------------
        // Save the updated payload back into the event so it becomes part of the token
        // ---------------------------
        $event->setData($data);
    }
}
