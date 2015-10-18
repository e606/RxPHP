<?php

namespace Rx\React;

use Rx\ObservableInterface;
use Rx\Observable\BaseObservable;
use Rx\Observer\CallbackObserver;
use Rx\Subject\AsyncSubject;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

final class Promise
{
    /**
     * Converts an existing observable sequence to React Promise
     *
     * @param PromisorInterface|null $deferred
     * @return \React\Promise\Promise
     */
    public static function fromObservable(ObservableInterface $observable, Deferred $deferred = null)
    {
        $d     = $deferred ?: new Deferred();
        $value = null;

        $observable->subscribe(new CallbackObserver(
          function ($v) use (&$value) {
              $value = $v;
          },
          function ($error) use ($d) {
              $d->reject($error);
          },
          function () use ($d, &$value) {
              $d->resolve($value);
          }
        ));

        return $d->promise();
    }

    /**
     * Converts a Promise to an Observable sequence
     *
     * @param \React\Promise\PromiseInterface $promise
     * @return \Rx\Observable\AnonymousObservable
     */
    public static function toObservable(PromiseInterface $promise)
    {
        return BaseObservable::defer(
          function () use ($promise) {
              $subject = new AsyncSubject();

              $promise->then(
                function ($value) use ($subject) {
                    $subject->onNext($value);
                    $subject->onCompleted();
                },
                [$subject, "onError"]
              );

              return $subject;
          });
    }
}
