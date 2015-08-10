<?php
abstract class AbstractRedirect extends AbstractResponse {

    public abstract function getRedirectTarget();
}