<?php

namespace Errbit\Errors;

/**
 *
 */
interface ErrorInterface
{
    
    /**
     * @return string
     */
    public function getMessage(): string;
    
    /**
     * Line getter
     *
     * @return integer the number of line
     */
    public function getLine(): int;
   
    /**
     * File getter
     *
     * @return string name of the file
     */
    public function getFile(): string;
    
    /**
     * @return array
     */
    public function getTrace(): array;
}
