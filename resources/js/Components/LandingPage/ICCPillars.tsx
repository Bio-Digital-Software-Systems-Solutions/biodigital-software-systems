import React from 'react';

const ICCPillars: React.FC = () => {
  return (
    <section className="py-24 bg-white dark:bg-gray-900">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <h2 className="text-4xl md:text-5xl font-bold mb-6 text-gray-900 dark:text-white">
            Impact Centre Chrétien
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
            Un centre d'excellence, de transformation, de puissance spirituelle, de formation, d'adoration, de communion et d'expression.
          </p>
        </div>

        <div className="max-w-5xl mx-auto">
          <img
            src="/vision_missions_icc.png"
            alt="Impact Centre Chrétien - Les 12 Piliers"
            className="w-full h-auto rounded-lg shadow-2xl"
          />
        </div>

        <div className="mt-16 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
          <div className="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
            <div className="text-2xl font-bold text-primary dark:text-blue-400 mb-2">1</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Refuge</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg">
            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400 mb-2">2</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Transformation</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg">
            <div className="text-2xl font-bold text-primary dark:text-indigo-400 mb-2">3</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Puissance spirituelle</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-violet-50 to-violet-100 dark:from-violet-900/20 dark:to-violet-800/20 rounded-lg">
            <div className="text-2xl font-bold text-violet-600 dark:text-violet-400 mb-2">4</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Formation</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 rounded-lg">
            <div className="text-2xl font-bold text-pink-600 dark:text-pink-400 mb-2">5</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Adoration</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-900/20 dark:to-rose-800/20 rounded-lg">
            <div className="text-2xl font-bold text-rose-600 dark:text-rose-400 mb-2">6</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Excellence</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-lg">
            <div className="text-2xl font-bold text-amber-600 dark:text-amber-400 mb-2">7</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Édification et d'épanouissement</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-lg">
            <div className="text-2xl font-bold text-orange-600 dark:text-orange-400 mb-2">8</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Puissance économique et financière</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-lg">
            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">9</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Communion et d'expression</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 rounded-lg">
            <div className="text-2xl font-bold text-teal-600 dark:text-teal-400 mb-2">10</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">En devenir</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-lg">
            <div className="text-2xl font-bold text-cyan-600 dark:text-cyan-400 mb-2">11</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Multiracial</h3>
          </div>

          <div className="text-center p-4 bg-gradient-to-br from-sky-50 to-sky-100 dark:from-sky-900/20 dark:to-sky-800/20 rounded-lg">
            <div className="text-2xl font-bold text-sky-600 dark:text-sky-400 mb-2">12</div>
            <h3 className="font-semibold text-gray-900 dark:text-white text-sm">Impact</h3>
          </div>
        </div>
      </div>
    </section>
  );
};

export default ICCPillars;
