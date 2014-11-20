<?php
  namespace Glome\ApiBundle\Entity;

  class GlomeAuthenticationUser  {

    protected $glomeid;

    protected $password;

    /**
     * Get Client object.
     *
     * @return integer
     */
    public function getGlomeId() {
      return $this->glomeid;
    }

    /**
     * Get Client object.
     *
     * @return string
     */
    public function getPassword() {
      return $this->password;
    }

    /**
     * Set Client object.
     * @param $password
     * @return self
     */
    public function setGlomeId($glomeid) {
      $this->glomeid = $glomeid;
      return $this;
    }

    /**
     * Set Client object.
     * @param $password
     * @return self
     */
    public function setPassword($password) {
      $this->password = $password;
      return $this;
    }
  }
