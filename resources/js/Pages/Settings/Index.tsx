import React, { useState } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';
import { toast } from 'sonner';
import {
  Bell,
  Shield,
  User,
  Mail,
  Lock,
  Globe,
  Palette,
  CheckCircle2
} from 'lucide-react';

interface UserSettings {
  email_notifications: boolean;
  sms_notifications: boolean;
  push_notifications: boolean;
  newsletter: boolean;
  event_reminders: boolean;
  training_updates: boolean;
  message_notifications: boolean;
}

interface Props {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      first_name?: string;
      last_name?: string;
      phone?: string;
    };
  };
  settings?: UserSettings;
}

export default function SettingsIndex({ auth, settings: initialSettings }: Props) {
  const [activeTab, setActiveTab] = useState('notifications');

  // Initialize with default values if settings is undefined
  const defaultSettings: UserSettings = {
    email_notifications: true,
    sms_notifications: false,
    push_notifications: true,
    newsletter: false,
    event_reminders: true,
    training_updates: true,
    message_notifications: true,
  };

  const { data, setData, post, processing } = useForm<UserSettings>(
    initialSettings || defaultSettings
  );

  const handleToggle = (key: keyof UserSettings) => {
    const newValue = !data[key];
    setData(key, newValue);

    // Save immediately on toggle
    post(route('settings.update'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Param�tre mis � jour', {
          description: 'Vos pr�f�rences ont �t� sauvegard�es.',
        });
      },
      onError: () => {
        toast.error('Erreur', {
          description: 'Impossible de sauvegarder vos pr�f�rences.',
        });
        // Revert on error
        setData(key, !newValue);
      },
    });
  };

  return (
    <DashboardLayout>
      <Head title="Param�tres" />

      <div className="py-8 px-4 sm:px-6 lg:px-8">
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Param�tres</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            G�rez vos pr�f�rences et param�tres de compte
          </p>
        </div>

        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
          <TabsList className="grid w-full grid-cols-1 md:grid-cols-4 h-auto">
            <TabsTrigger value="notifications" className="flex items-center gap-2 py-3">
              <Bell className="h-4 w-4" />
              <span className="hidden sm:inline">Notifications</span>
            </TabsTrigger>
            <TabsTrigger value="account" className="flex items-center gap-2 py-3">
              <User className="h-4 w-4" />
              <span className="hidden sm:inline">Compte</span>
            </TabsTrigger>
            <TabsTrigger value="privacy" className="flex items-center gap-2 py-3">
              <Shield className="h-4 w-4" />
              <span className="hidden sm:inline">Confidentialit�</span>
            </TabsTrigger>
            <TabsTrigger value="preferences" className="flex items-center gap-2 py-3">
              <Palette className="h-4 w-4" />
              <span className="hidden sm:inline">Pr�f�rences</span>
            </TabsTrigger>
          </TabsList>

          {/* Notifications Tab */}
          <TabsContent value="notifications" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Bell className="h-5 w-5" />
                  Notifications
                </CardTitle>
                <CardDescription>
                  Choisissez comment vous souhaitez �tre notifi� des activit�s
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="space-y-4">
                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="email-notifications" className="text-base font-medium">
                        Notifications par email
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez des notifications importantes par email
                      </p>
                    </div>
                    <Switch
                      id="email-notifications"
                      checked={data.email_notifications}
                      onCheckedChange={() => handleToggle('email_notifications')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="sms-notifications" className="text-base font-medium">
                        Notifications SMS
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez des alertes importantes par SMS
                      </p>
                    </div>
                    <Switch
                      id="sms-notifications"
                      checked={data.sms_notifications}
                      onCheckedChange={() => handleToggle('sms_notifications')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="push-notifications" className="text-base font-medium">
                        Notifications push
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez des notifications push dans votre navigateur
                      </p>
                    </div>
                    <Switch
                      id="push-notifications"
                      checked={data.push_notifications}
                      onCheckedChange={() => handleToggle('push_notifications')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="message-notifications" className="text-base font-medium">
                        Notifications de messages
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Soyez inform� quand vous recevez un nouveau message
                      </p>
                    </div>
                    <Switch
                      id="message-notifications"
                      checked={data.message_notifications}
                      onCheckedChange={() => handleToggle('message_notifications')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="training-updates" className="text-base font-medium">
                        Mises � jour de formations
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez des notifications sur vos formations
                      </p>
                    </div>
                    <Switch
                      id="training-updates"
                      checked={data.training_updates}
                      onCheckedChange={() => handleToggle('training_updates')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="event-reminders" className="text-base font-medium">
                        Rappels d'�v�nements
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez des rappels pour les �v�nements � venir
                      </p>
                    </div>
                    <Switch
                      id="event-reminders"
                      checked={data.event_reminders}
                      onCheckedChange={() => handleToggle('event_reminders')}
                      disabled={processing}
                    />
                  </div>

                  <div className="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700">
                    <div className="space-y-0.5">
                      <Label htmlFor="newsletter" className="text-base font-medium">
                        Newsletter
                      </Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Recevez notre newsletter mensuelle
                      </p>
                    </div>
                    <Switch
                      id="newsletter"
                      checked={data.newsletter}
                      onCheckedChange={() => handleToggle('newsletter')}
                      disabled={processing}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Account Tab */}
          <TabsContent value="account" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <User className="h-5 w-5" />
                  Informations du compte
                </CardTitle>
                <CardDescription>
                  G�rez vos informations personnelles et de s�curit�
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="p-4 rounded-lg border dark:border-gray-700">
                    <Label className="text-sm text-gray-600 dark:text-gray-400">Nom</Label>
                    <p className="text-base font-medium mt-1">
                      {auth.user.first_name} {auth.user.last_name}
                    </p>
                  </div>
                  <div className="p-4 rounded-lg border dark:border-gray-700">
                    <Label className="text-sm text-gray-600 dark:text-gray-400">Email</Label>
                    <p className="text-base font-medium mt-1 flex items-center gap-2">
                      {auth.user.email}
                      <Badge variant="outline" className="text-xs">
                        <CheckCircle2 className="h-3 w-3 mr-1" />
                        V�rifi�
                      </Badge>
                    </p>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Link href={route('profile.edit')}>
                    <Button variant="outline" className="w-full">
                      <User className="h-4 w-4 mr-2" />
                      Modifier le profil
                    </Button>
                  </Link>
                  <Link href={route('profile.edit')}>
                    <Button variant="outline" className="w-full">
                      <Lock className="h-4 w-4 mr-2" />
                      Changer le mot de passe
                    </Button>
                  </Link>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Privacy Tab */}
          <TabsContent value="privacy" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  Confidentialité et sécurité
                </CardTitle>
                <CardDescription>
                  Contrôlez la visibilité de vos informations
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="p-4 rounded-lg border dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                  <div className="flex items-start gap-3">
                    <Shield className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                      <h3 className="font-medium mb-1">Vos donn�es sont prot�g�es</h3>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Nous utilisons des mesures de s�curit� avanc�es pour prot�ger vos informations personnelles.
                        Vos donn�es ne sont jamais partag�es avec des tiers sans votre consentement explicite.
                      </p>
                    </div>
                  </div>
                </div>

                <div className="space-y-3">
                  <Link href="/privacy-policy" target="_blank">
                    <Button variant="outline" className="w-full justify-start">
                      <Shield className="h-4 w-4 mr-2" />
                      Politique de confidentialit�
                    </Button>
                  </Link>
                  <Link href="/terms-of-service" target="_blank">
                    <Button variant="outline" className="w-full justify-start">
                      <Globe className="h-4 w-4 mr-2" />
                      Conditions d'utilisation
                    </Button>
                  </Link>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Preferences Tab */}
          <TabsContent value="preferences" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Palette className="h-5 w-5" />
                  Pr�f�rences d'affichage
                </CardTitle>
                <CardDescription>
                  Personnalisez l'apparence de l'application
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="p-4 rounded-lg border dark:border-gray-700">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base font-medium">Th�me</Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Le th�me est contr�l� par le bouton dans la barre de navigation
                      </p>
                    </div>
                    <Badge variant="outline">Automatique</Badge>
                  </div>
                </div>

                <div className="p-4 rounded-lg border dark:border-gray-700">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base font-medium">Langue</Label>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        La langue est contr�l�e par le s�lecteur dans la barre de navigation
                      </p>
                    </div>
                    <Badge variant="outline">Fran�ais</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
