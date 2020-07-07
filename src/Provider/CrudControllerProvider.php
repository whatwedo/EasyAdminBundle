<?php


namespace EasyCorp\Bundle\EasyAdminBundle\Provider;


use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudControllerProvider
{
    private $crudControllers;
    private $controllerResolver;

    public function __construct(CrudControllerRegistry $crudControllers, ControllerResolverInterface $controllerResolver)
    {
        $this->crudControllers = $crudControllers;
        $this->controllerResolver = $controllerResolver;
    }

    public function getCrudControllerInstance(?string $crudId, ?string $crudAction, Request $request): ?CrudControllerInterface
    {
        if (null === $crudId || null === $crudAction) {
            return null;
        }

        if (null === $crudControllerFqcn = $this->crudControllers->findCrudFqcnByCrudId($crudId)) {
            return null;
        }

        $newRequest = $request->duplicate(null, null, ['_controller' => [$crudControllerFqcn, $crudAction]]);
        $crudControllerCallable = $this->controllerResolver->getController($newRequest);

        if (false === $crudControllerCallable) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller "%s::%s".', $crudControllerFqcn, $crudAction));
        }

        if (!\is_array($crudControllerCallable)) {
            return null;
        }

        $crudControllerInstance = $crudControllerCallable[0];

        return $crudControllerInstance instanceof CrudControllerInterface ? $crudControllerInstance : null;
    }
}
