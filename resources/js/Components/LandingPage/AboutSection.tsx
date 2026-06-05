import React from 'react';
import { Badge } from '../ui/badge';
import WorldMap from './WorldMap';
import { resolveDesign, type DesignSettings } from '@/lib/sectionDesign';

interface GlobalStats {
  total_churches: number;
  total_countries: number;
  total_members: number;
  europe: number;
  africa: number;
  americas: number;
  asia: number;
  oceania: number;
}

export interface AboutContent {
  badge?: string;
  heading?: string;
  paragraphs?: string[];
  image_url?: string;
  mission_blocks?: Array<{ title: string; body: string; color?: string }>;
  stats?: Array<{ value: string; label: string; color?: string }>;
  affiliations?: Array<{ label: string; color?: string }>;
}

interface AboutSectionProps {
  globalStats: GlobalStats;
  content?: AboutContent;
  design?: DesignSettings | null;
}

const AboutSection: React.FC<AboutSectionProps> = ({ globalStats, content, design }) => {
  const resolved = resolveDesign(design);
  const badge = content?.badge ?? 'À propos';
  const heading = content?.heading ?? "Une famille d'églises qui transforme des vies";
  const paragraphs = content?.paragraphs ?? [
    "L'Impact Centre Chrétien (ICC) est une famille d'églises charismatiques fondées en France en 2002 par les pasteurs Yves et Yvan Castanou, qui vise à former des disciples pour qu'ils exercent une influence positive dans la société.",
    "L'ICC diffuse son message par les médias et les nouvelles technologies, propose des formations pour le développement spirituel et met en œuvre des actions humanitaires via sa branche Impact Sans Frontières (ISF).",
  ];
  const imageUrl = content?.image_url ?? '/vision_missions_icc.png';

  return (
    <div
      id="about"
      className={`bg-gray-50 dark:bg-gray-900 px-6 md:px-12 ${resolved.sectionClass} ${resolved.hasPadding ? '' : 'py-16'}`}
      style={resolved.sectionStyle}
    >
      <div className={`space-y-4 mb-16 ${resolved.alignmentClass || 'text-center'}`}>
        <Badge variant="secondary" className="mb-2">
          {badge}
        </Badge>
      </div>
      <div className="max-w-7xl mx-auto">

        {/* Section 1: Notre Histoire */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-24">
          {/* Left side - Text content */}
          <div className="space-y-6">
            <h1 className={`font-bold text-gray-900 dark:text-white leading-tight ${resolved.headingClass || 'text-5xl lg:text-6xl'}`}>
              {heading}
            </h1>
            {paragraphs.map((p, i) => (
              <p key={i} className={`text-gray-500 dark:text-gray-400 leading-relaxed ${resolved.paragraphClass || 'text-lg'}`}>
                {p}
              </p>
            ))}
          </div>

          {/* Right side - ICC Pillars Image */}
          <div className="flex items-center justify-center">
            <img
              src={imageUrl}
              alt={heading}
              className="w-full h-auto max-h-[32rem] object-contain rounded-lg"
            />
          </div>
        </div>

        {/* Section 2: Vision et Mission */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-16 mb-24">
          {/* Left side - Mission text (2/3 width) */}
          <div className="lg:col-span-2 space-y-8">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white">
              Notre Vision et Mission
            </h2>
            <div className="space-y-6 text-gray-500 dark:text-gray-400 text-lg leading-relaxed">
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-primary">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">Former des disciples</h3>
                <p>L'objectif principal est de former des chrétiens qui influencent leur environnement.</p>
              </div>
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-green-500">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">L'impact sur le monde</h3>
                <p>L'ICC veut avoir un impact positif sur la société, en accord avec les plans de Dieu, en créant de bons résultats.</p>
              </div>
              <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border-l-4 border-purple-500">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">L'église sans barrière</h3>
                <p>La diffusion du message de l'église par les médias et les nouvelles technologies vise à toucher un large public, sans frontières.</p>
              </div>
            </div>
          </div>

          {/* Right side - Statistics (1/3 width) */}
          <div className="space-y-12 text-center">
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-primary dark:text-blue-400 mb-2">2002</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">Année de fondation</div>
            </div>
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">Global</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">Présence mondiale</div>
            </div>
            <div className="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
              <div className="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">FPF</div>
              <div className="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">Membre officiel</div>
            </div>
          </div>
        </div>

        {/* Section 3: Activités et Programmes */}
        <div className="mb-24">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-12 text-center">
            Nos Activités et Programmes
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* PCNC */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-primary dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">Formations PCNC</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                Parcours de Croissance de la Nouvelle Création pour aider les membres à grandir dans leur foi et leur transformation personnelle.
              </p>
            </div>

            {/* Impact Sans Frontières */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">Impact Sans Frontières</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                Une branche dédiée aux actions de compassion, de soutien et d'évangélisation à travers le monde.
              </p>
            </div>

            {/* Plateformes digitales */}
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className="w-16 h-16 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center mb-6">
                <svg className="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">Plateformes Digitales</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                Des outils comme l'application SmartICC et des plateformes pour toutes les générations, notamment la plateforme des femmes.
              </p>
            </div>
          </div>
        </div>

        {/* Section 4: World Map - Notre Présence Mondiale */}
        <div className="mb-24">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6 text-center">
            Notre Présence Mondiale
          </h2>
          <p className="text-gray-500 dark:text-gray-400 text-lg text-center mb-12 max-w-3xl mx-auto">
            L'ICC est présente sur plusieurs continents avec des églises dynamiques qui transforment des vies et impactent leurs communautés.
          </p>
          <div className="relative rounded-3xl overflow-hidden bg-white dark:bg-gray-800 shadow-2xl p-4">
            <WorldMap globalStats={globalStats} />
          </div>
        </div>

        {/* Section 5: Structure et Affiliations */}
        <div className="max-w-6xl mb-8">
          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
            Structure et Affiliations
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg">
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">Famille d'Églises</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                L'ICC est une famille d'églises multilocales implantées en France et dans le monde,
                créant un réseau de communautés connectées par une vision commune.
              </p>
            </div>
            <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-lg">
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">Affiliations Officielles</h3>
              <div className="space-y-3">
                <div className="flex items-center space-x-3">
                  <div className="w-3 h-3 bg-primary rounded-full"></div>
                  <span className="text-gray-600 dark:text-gray-400">Membre de la Fédération protestante de France (FPF)</span>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span className="text-gray-600 dark:text-gray-400">Affilié à la Communauté des Églises d'expressions africaines en France (CEAF)</span>
                </div>
              </div>
            </div>
          </div>

          {/* Diffusion du message */}
          <div className="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-8 rounded-2xl mb-8">
            <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">Diffusion du Message</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="text-center">
                <div className="w-12 h-12 bg-primary dark:bg-primary rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10l1 1v16l-1 1H6l-1-1V5l1-1z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">Médias Sociaux</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">Instagram, Facebook et autres plateformes pour toucher un large public</p>
              </div>
              <div className="text-center">
                <div className="w-12 h-12 bg-purple-500 dark:bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">Site Web</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">Plateforme centrale pour la diffusion de contenu et les ressources</p>
              </div>
              <div className="text-center">
                <div className="w-12 h-12 bg-green-500 dark:bg-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                  <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <h4 className="font-bold text-gray-900 dark:text-white mb-2">Technologies</h4>
                <p className="text-gray-600 dark:text-gray-400 text-sm">Applications mobiles et outils numériques innovants</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};

export default AboutSection;
