<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Entity\Traits\Habilitado;
use App\Repository\UsuarioRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UsuarioRepository::class)
 *
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Usuario implements UserInterface {

    use Auditoria;
    use Habilitado;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=180, unique=true, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apellido;

    /**
     * @ORM\Column(type="string", length=20, nullable=true, unique=true)
     */
    private $cuit;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string", nullable=true)
     */
    private $password;

    /**
     * @ORM\ManyToMany(targetEntity="Grupo", inversedBy="usuarios")
     * @ORM\JoinTable(name="usuario_grupo")
     * @Assert\NotNull()
     * @Assert\Count(min=1, minMessage="Debe tener al menos {{ limit }} grupo asignado")
     */
    protected $grupos;

    /**
     * @var string
     *
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var \Datetime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastSeen;

    /**
     * @ORM\ManyToOne(targetEntity=TipoUsuario::class)
     * @ORM\JoinColumn(name="id_tipo_usuario", referencedColumnName="id", nullable=true)
     */
    private $tipoUsuario;

    /**
     * @ORM\ManyToOne(targetEntity=RazonSocial::class, cascade={"persist"})
     * @ORM\JoinColumn(name="id_razon_social", referencedColumnName="id", nullable=true)
     */
    private $razonSocial;

    /**
     * @var string
     *
     * @ORM\Column(name="telefono", type="string", length=50, nullable=true)
     */
    protected $telefono;

    /**
     * @var string
     *
     * @ORM\Column(name="celular", type="string", length=50, nullable=true)
     */
    protected $celular;

    /**
     * @var string
     *
     * @ORM\Column(name="domicilio", type="string", length=255, nullable=true)
     */
    protected $domicilio;

    /**
     * @var boolean
     *
     * @ORM\Column(name="tiene_razon_social", type="boolean", nullable=true, options={"default": 0})
     */
    private $tieneRazonSocial;

    /**
     * @ORM\OneToOne(targetEntity=CuentaCorriente::class, inversedBy="cliente")
     * @ORM\JoinColumn(name="id_cuenta_corriente", referencedColumnName="id")
     */
    private $cuentaCorriente;

    /**
     * @ORM\OneToMany(targetEntity=Pedido::class, mappedBy="cliente", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $pedidos;

    /**
     * @ORM\OneToMany(targetEntity=Remito::class, mappedBy="cliente", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $remitos;

    /**
     * @ORM\OneToMany(targetEntity=Entrega::class, mappedBy="cliente", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $entregas;

    public function __construct() {
        $this->grupos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->habilitado = true;
        $this->tieneRazonSocial = false;
        $this->pedidos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->remitos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->entregas = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function __toString(): string {
        $nombre = $this->getApellido(). ' '.$this->getNombre();
        if ($this->getTipoUsuario()->getCodigoInterno() == Constants\ConstanteTipoUsuario::CLIENTE){
            if ($this->razonSocial != null){
                $nombre.=' ('.$this->getRazonSocial().')';
            }
        }
        return $nombre;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(string $email): self {
        $this->email = $email;

        return $this;
    }

    public function getNombre(): ?string {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(): ?string {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): self {
        $this->apellido = $apellido;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function setUsername($username): self {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string {
        return (string) $this->username;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array {
        $roles = [];

        $roles[] = 'ROLE_USER';

        foreach ($this->getGrupos() as $grupo) {
            $roles = array_merge($roles, $grupo->getRoles());
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string {
        return (string) $this->password;
    }

    public function setPassword(string $password): self {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt() {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials() {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getGrupos() {
        return $this->grupos ?: $this->grupos = new ArrayCollection();
    }

    /**
     * Set confirmationToken
     *
     * @param string $confirmationToken
     * @return Usuario
     */
    public function setConfirmationToken($confirmationToken): Usuario {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Get confirmationToken
     *
     * @return string
     */
    public function getConfirmationToken(): ?string {
        return $this->confirmationToken;
    }

    /**
     * Add grupo
     *
     * @param Grupo $grupo
     * @return Usuario
     */
    public function addGrupo(Grupo $grupo) {
        $this->grupos[] = $grupo;

        return $this;
    }

    /**
     * Remove grupo
     *
     * @param Grupo $grupo
     */
    public function removeGrupo(Grupo $grupo) {
        $this->grupos->removeElement($grupo);
    }

    /**
     * @return \Datetime
     */
    public function getLastSeen() {
        return $this->lastSeen;
    }

    /**
     *
     * @param type $lastSeen
     * @return self
     */
    public function setLastSeen($lastSeen): self {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function isActiveNow(): bool {
        $delay = new DateTime('2 minutes ago');

        return ( $this->getLastSeen() > $delay );
    }

    /**
     * Get the value of cuit
     */
    public function getCuit() {
        return $this->cuit;
    }

    /**
     * Set the value of cuit
     *
     * @return  self
     */
    public function setCuit($cuit) {
        $this->cuit = $cuit;

        return $this;
    }

    /**
     *
     * @return type
     */
    public function getNombreCompleto() {
        return $this->nombre . ' ' . $this->apellido;
    }
    /**
     * @return int
     */
    public function getHabilitado(): int
    {
        return $this->habilitado;
    }

    /**
     * @param int $habilitado
     */
    public function setHabilitado(int $habilitado): void
    {
        $this->habilitado = $habilitado;
    }

    /**
     * @return mixed
     */
    public function getTipoUsuario()
    {
        return $this->tipoUsuario;
    }

    /**
     * @param mixed $tipoUsuario
     */
    public function setTipoUsuario($tipoUsuario): void
    {
        $this->tipoUsuario = $tipoUsuario;
    }

    /**
     * @return mixed
     */
    public function getRazonSocial()
    {
        return $this->razonSocial;
    }

    /**
     * @param mixed $razonSocial
     */
    public function setRazonSocial($razonSocial): void
    {
        $this->razonSocial = $razonSocial;
    }

    /**
     * @return string
     */
    public function getDomicilio()
    {
        return $this->domicilio;
    }

    /**
     * @param string $domicilio
     */
    public function setDomicilio(string $domicilio): void
    {
        $this->domicilio = $domicilio;
    }

    /**
     * @return string
     */
    public function getTelefono()
    {
        return $this->telefono;
    }

    /**
     * @param string $telefono
     */
    public function setTelefono(string $telefono): void
    {
        $this->telefono = $telefono;
    }

    /**
     * @return string
     */
    public function getCelular()
    {
        return $this->celular;
    }

    /**
     * @param string $celular
     */
    public function setCelular(string $celular): void
    {
        $this->celular = $celular;
    }

    /**
     * @return bool
     */
    public function getTieneRazonSocial()
    {
        return $this->tieneRazonSocial;
    }

    /**
     * @param bool $tieneRazonSocial
     */
    public function setTieneRazonSocial($tieneRazonSocial = false)
    {
        $this->tieneRazonSocial = $tieneRazonSocial;
    }

    public function getCuentaCorriente(): CuentaCorriente|null
    {
        return $this->cuentaCorriente;
    }

    public function setCuentaCorriente(CuentaCorriente $cuentaCorriente): void
    {
        $this->cuentaCorriente = $cuentaCorriente;
    }

    /**
     * @return mixed
     */
    public function getPedidos()
    {
        return $this->pedidos;
    }

    /**
     * @param mixed $pedidos
     */
    public function setPedidos($pedidos): void
    {
        $this->pedidos = $pedidos;
    }

    /**
     * @return mixed
     */
    public function getRemitos()
    {
        return $this->remitos;
    }

    /**
     * @param mixed $remitos
     */
    public function setRemitos($remitos): void
    {
        $this->remitos = $remitos;
    }

    public function getPendiente(){
        $pendiente = 0;
        foreach ($this->getRemitos() as $remito){
            $pendiente+=$remito->getPendiente();
        }
        return $pendiente;
    }

    public function getEntregas(): ArrayCollection
    {
        return $this->entregas;
    }

    public function setEntregas(ArrayCollection $entregas): void
    {
        $this->entregas = $entregas;
    }







}
