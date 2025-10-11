import React, { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Clock, AlertTriangle } from 'lucide-react';

interface Quiz {
  id: number;
  title: string;
  description: string | null;
  duration_minutes: number;
  max_score: number;
  passing_score: number;
}

interface Attempt {
  id: number;
  started_at: string;
  time_remaining_seconds: number;
}

interface Props {
  quiz: Quiz;
  attempt: Attempt;
}

export default function Take({ quiz, attempt }: Props) {
  const [timeRemaining, setTimeRemaining] = useState(attempt.time_remaining_seconds);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const timerRef = useRef<NodeJS.Timeout | null>(null);
  const hasAutoSubmitted = useRef(false);

  // Format time as MM:SS
  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  // Auto-submit when time runs out
  useEffect(() => {
    if (timeRemaining <= 0 && !hasAutoSubmitted.current && !isSubmitting) {
      hasAutoSubmitted.current = true;
      handleSubmit();
    }
  }, [timeRemaining]);

  // Timer countdown - UNSTOPPABLE
  useEffect(() => {
    timerRef.current = setInterval(() => {
      setTimeRemaining((prev) => {
        if (prev <= 0) {
          if (timerRef.current) {
            clearInterval(timerRef.current);
          }
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    // Prevent page unload
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      e.preventDefault();
      e.returnValue = 'Le quiz est en cours. Si vous quittez, le timer continuera et le quiz sera soumis automatiquement.';
      return e.returnValue;
    };

    window.addEventListener('beforeunload', handleBeforeUnload);

    // Cleanup
    return () => {
      if (timerRef.current) {
        clearInterval(timerRef.current);
      }
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, []);

  const handleSubmit = () => {
    if (isSubmitting) return;

    setIsSubmitting(true);

    // TODO: Calculer le vrai score basé sur les réponses
    const score = 0; // Score fictif pour l'instant

    router.post(
      route('quiz-attempts.submit', attempt.id),
      {
        answers: [],
        score: score,
      },
      {
        onFinish: () => setIsSubmitting(false),
      }
    );
  };

  const isLowTime = timeRemaining > 0 && timeRemaining <= 300; // 5 minutes
  const isCriticalTime = timeRemaining > 0 && timeRemaining <= 60; // 1 minute

  return (
    <>
      <Head title={`Quiz: ${quiz.title}`} />

      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div className="max-w-4xl mx-auto px-4">
          {/* Timer - Fixed at top */}
          <div className="sticky top-0 z-50 mb-6">
            <Card className={`border-2 ${
              isCriticalTime
                ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                : isLowTime
                ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20'
                : 'border-primary bg-blue-50 dark:bg-blue-900/20'
            }`}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Clock className={`h-6 w-6 ${
                      isCriticalTime ? 'text-red-600' : isLowTime ? 'text-orange-600' : 'text-primary'
                    }`} />
                    <div>
                      <p className="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Temps restant
                      </p>
                      <p className={`text-3xl font-bold ${
                        isCriticalTime ? 'text-red-600' : isLowTime ? 'text-orange-600' : 'text-primary'
                      }`}>
                        {formatTime(timeRemaining)}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Durée totale: {quiz.duration_minutes} minutes
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">
                      Soumission automatique à 00:00
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Warning Alert */}
          {(isLowTime || isCriticalTime) && (
            <Alert className={`mb-6 ${
              isCriticalTime ? 'border-red-500 bg-red-50' : 'border-orange-500 bg-orange-50'
            }`}>
              <AlertTriangle className={`h-4 w-4 ${
                isCriticalTime ? 'text-red-600' : 'text-orange-600'
              }`} />
              <AlertDescription className={
                isCriticalTime ? 'text-red-800' : 'text-orange-800'
              }>
                {isCriticalTime
                  ? 'ATTENTION: Moins d\'une minute restante!'
                  : 'Attention: Il vous reste moins de 5 minutes!'}
              </AlertDescription>
            </Alert>
          )}

          {/* Quiz Content */}
          <Card>
            <CardHeader>
              <CardTitle className="text-2xl">{quiz.title}</CardTitle>
              {quiz.description && (
                <p className="text-gray-600 dark:text-gray-400 mt-2">
                  {quiz.description}
                </p>
              )}
            </CardHeader>
            <CardContent>
              <div className="space-y-6">
                <Alert>
                  <AlertDescription>
                    <strong>Instructions importantes:</strong>
                    <ul className="list-disc list-inside mt-2 space-y-1">
                      <li>Le timer ne peut pas être arrêté une fois le quiz commencé</li>
                      <li>Si vous fermez cette page, le timer continuera</li>
                      <li>Le quiz sera automatiquement soumis à la fin du temps</li>
                      <li>Score de passage: {quiz.passing_score}/{quiz.max_score}</li>
                    </ul>
                  </AlertDescription>
                </Alert>

                {/* TODO: Ajouter les questions du quiz ici */}
                <div className="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg text-center">
                  <p className="text-gray-600 dark:text-gray-400">
                    Les questions du quiz apparaîtront ici.
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-500 mt-2">
                    Pour l'instant, c'est une démo du système de timer.
                  </p>
                </div>

                {/* Submit Button */}
                <div className="flex justify-end gap-4 pt-6 border-t">
                  <Button
                    onClick={handleSubmit}
                    disabled={isSubmitting || timeRemaining <= 0}
                    className="bg-green-600 hover:bg-green-700"
                  >
                    {isSubmitting ? 'Soumission...' : 'Soumettre le quiz'}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  );
}
