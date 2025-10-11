import React from 'react';
import { Heart, Users, Shield, CheckCircle, Handshake, Eye } from 'lucide-react';

interface Value {
  icon: React.ReactNode;
  letter: string;
  title: string;
  description: string;
}

const values: Value[] = [
  {
    icon: <Heart className="h-6 w-6" />,
    letter: 'C',
    title: 'Consécration',
    description: 'Dévotion et engagement envers Dieu et ses principes.',
  },
  {
    icon: <Users className="h-6 w-6" />,
    letter: 'H',
    title: 'Humilité',
    description: 'Une attitude de modestie et d\'abaissement devant Dieu et les autres.',
  },
  {
    icon: <Shield className="h-6 w-6" />,
    letter: 'R',
    title: 'Respect',
    description: 'Honneur et considération pour les personnes et les principes divins.',
  },
  {
    icon: <CheckCircle className="h-6 w-6" />,
    letter: 'I',
    title: 'Intégrité',
    description: 'Honnêteté et droiture dans toutes les actions.',
  },
  {
    icon: <Handshake className="h-6 w-6" />,
    letter: 'S',
    title: 'Service',
    description: 'Volonté d\'aider et de servir les autres.',
  },
  {
    icon: <Eye className="h-6 w-6" />,
    letter: 'T',
    title: 'Transparence',
    description: 'Clarté et honnêteté dans la communication et les actions.',
  },
];

const OurValues: React.FC = () => {
  return (
    <section className="pt-0 pb-24 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="max-w-4xl mb-16">
          <h2 className="text-4xl md:text-5xl font-bold mb-6 text-gray-900 dark:text-white">
            Nos valeurs
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-300 leading-relaxed mb-6">
            Les valeurs d'Impact Centre Chrétien sont axées sur les principes du Royaume de Dieu et sont représentées par l'acronyme <span className="font-bold text-violet-600 dark:text-violet-400">C.H.R.I.S.T</span> :
            Consécration, Humilité, Respect, Intégrité, Service, et Transparence.
          </p>
          <p className="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
            L'église vise à former des personnes qui manifestent l'amour, l'acceptation et l'espérance, et qui influent positivement sur leur environnement pour la gloire de Dieu.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
          {values.map((value, index) => (
            <div key={index} className="flex gap-4">
              <div className="flex-shrink-0">
                <div className="relative">
                  <div className="flex items-center justify-center w-12 h-12 rounded-lg bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400">
                    {value.icon}
                  </div>
                  <div className="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-violet-600 dark:bg-violet-500 text-white flex items-center justify-center text-xs font-bold">
                    {value.letter}
                  </div>
                </div>
              </div>
              <div className="flex-1">
                <h3 className="text-xl font-bold mb-3 text-gray-900 dark:text-white">
                  {value.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {value.description}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default OurValues;
