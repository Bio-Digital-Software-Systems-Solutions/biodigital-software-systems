import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import * as d3 from 'd3';
import { feature } from 'topojson-client';
import { iccChurches, getIccStats, type Church } from '@/data/iccChurches';

const WorldMap: React.FC = () => {
  const svgRef = useRef<SVGSVGElement>(null);
  const tooltipRef = useRef<HTMLDivElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const [selectedChurch, setSelectedChurch] = useState<Church | null>(null);
  const [isMounted, setIsMounted] = useState(false);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const stats = getIccStats();

  useEffect(() => {
    setIsMounted(true);
  }, []);

  useEffect(() => {
    const handleFullscreenChange = () => {
      setIsFullscreen(!!document.fullscreenElement);
    };

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => {
      document.removeEventListener('fullscreenchange', handleFullscreenChange);
    };
  }, []);

  const toggleFullscreen = () => {
    if (!containerRef.current) return;

    if (!document.fullscreenElement) {
      containerRef.current.requestFullscreen();
    } else {
      document.exitFullscreen();
    }
  };

  useEffect(() => {
    if (!svgRef.current) return;

    const width = 1200;
    const height = 600;

    // Clear previous content to avoid memory leaks
    const svgElement = d3.select(svgRef.current);
    svgElement.selectAll('*').remove();

    const svg = d3.select(svgRef.current)
      .attr('viewBox', `0 0 ${width} ${height}`)
      .attr('preserveAspectRatio', 'xMidYMid meet');

    const projection = d3.geoMercator()
      .scale(180)
      .translate([width / 2, height / 1.5]);

    const path = d3.geoPath().projection(projection);

    const g = svg.append('g');

    // Load world map data
    d3.json('/countries-110m.json')
      .then((data: any) => {
        const countries: any = feature(data, data.objects.countries);

        // Draw countries
        g.selectAll('path')
          .data(countries.features as any[])
          .enter()
          .append('path')
          .attr('d', path as any)
          .attr('fill', '#e5e7eb')
          .attr('stroke', '#ffffff')
          .attr('stroke-width', 0.5)
          .style('transition', 'fill 0.3s')
          .on('mouseover', function() {
            d3.select(this).attr('fill', '#d1d5db');
          })
          .on('mouseout', function() {
            d3.select(this).attr('fill', '#e5e7eb');
          });

        // Create size scale for bubbles - augmentation de la taille
        const sizeScale = d3.scaleSqrt()
          .domain([0, d3.max(iccChurches, d => d.members || 0) || 1500])
          .range([6, 25]); // Taille min 6px, max 25px (augmentée)

        // Add glow filter BEFORE drawing bubbles
        const defs = svg.insert('defs', ':first-child');
        const filter = defs.append('filter')
          .attr('id', 'glow');
        filter.append('feGaussianBlur')
          .attr('stdDeviation', '3')
          .attr('result', 'coloredBlur');
        const feMerge = filter.append('feMerge');
        feMerge.append('feMergeNode').attr('in', 'coloredBlur');
        feMerge.append('feMergeNode').attr('in', 'SourceGraphic');

        // Draw churches as bubbles - Dans le groupe 'g' pour suivre le zoom/pan
        const bubbleGroup = g.append('g').attr('class', 'bubbles');

        // Créer un groupe pour chaque église (cercle + anneau)
        const churchGroups = bubbleGroup.selectAll('g.church-group')
          .data(iccChurches)
          .enter()
          .append('g')
          .attr('class', 'church-group')
          .attr('transform', d => {
            const coords = projection(d.coordinates);
            return `translate(${coords?.[0] || 0},${coords?.[1] || 0})`;
          })
          .style('cursor', 'pointer');

        // Anneau extérieur (ring) pour plus de visibilité
        churchGroups.append('circle')
          .attr('class', 'church-ring')
          .attr('r', 0)
          .attr('fill', 'none')
          .attr('stroke', '#a855f7')
          .attr('stroke-width', 2)
          .attr('opacity', 0.4)
          .style('pointer-events', 'none');

        // Bulle principale
        const bubbles = churchGroups.append('circle')
          .attr('class', 'church')
          .attr('r', 0)
          .attr('fill', '#a855f7')
          .attr('fill-opacity', 0.9)
          .attr('stroke', '#ffffff')
          .attr('stroke-width', 2)
          .style('filter', 'drop-shadow(0px 0px 8px rgba(168, 85, 247, 0.6))')
          .style('pointer-events', 'none');

        // Add hover zone - invisible larger circle to catch mouse events
        churchGroups.append('circle')
          .attr('class', 'church-hover-zone')
          .attr('r', d => Math.max(sizeScale(d.members || 100), 15)) // Minimum 15px for easy hovering
          .attr('fill', 'transparent')
          .attr('stroke', 'none')
          .on('mouseover', function(event, d) {
            // Animate the actual bubble (sibling element)
            d3.select(this.parentNode as Element).select('.church')
              .transition()
              .duration(200)
              .attr('r', sizeScale(d.members || 100) * 1.3)
              .attr('fill-opacity', 1);

            // Show tooltip
            if (tooltipRef.current) {
              const tooltip = d3.select(tooltipRef.current);
              tooltip
                .style('display', 'block')
                .style('opacity', '1')
                .style('left', (event.clientX + 15) + 'px')
                .style('top', (event.clientY - 30) + 'px')
                .html(`
                  <div class="font-bold text-gray-900 dark:text-white">${d.name}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-300">${d.city}, ${d.country}</div>
                  ${d.members ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${d.members.toLocaleString()} membres</div>` : ''}
                `);
            }
            setSelectedChurch(d);
          })
          .on('mousemove', function(event) {
            // Suivre le curseur
            if (tooltipRef.current) {
              d3.select(tooltipRef.current)
                .style('left', (event.clientX + 15) + 'px')
                .style('top', (event.clientY - 30) + 'px');
            }
          })
          .on('mouseout', function(event, d) {
            // Animate the actual bubble back
            d3.select(this.parentNode as Element).select('.church')
              .transition()
              .duration(200)
              .attr('r', sizeScale(d.members || 100))
              .attr('fill-opacity', 0.9);

            // Hide tooltip
            if (tooltipRef.current) {
              d3.select(tooltipRef.current)
                .style('opacity', 0)
                .style('display', 'none');
            }
          })
          .on('click', (event, d) => {
            setSelectedChurch(d);
          });

        // Animate bubbles - animation en cascade plus visible
        bubbles.transition()
          .duration(1200)
          .delay((d, i) => i * 30)
          .attr('r', d => sizeScale(d.members || 100))
          .ease(d3.easeElasticOut);

        // Animer les anneaux aussi
        bubbleGroup.selectAll('.church-ring')
          .transition()
          .duration(1200)
          .delay((d, i) => i * 30)
          .attr('r', (d: any) => sizeScale(d.members || 100) + 4)
          .ease(d3.easeElasticOut);

        // Add subtle pulse animation to bubbles only (simplified to avoid crash)
        const pulse = () => {
          bubbles
            .transition()
            .duration(2000)
            .attr('fill-opacity', 0.7)
            .transition()
            .duration(2000)
            .attr('fill-opacity', 0.9)
            .on('end', function() {
              // Appeler pulse seulement sur le premier élément pour éviter la surcharge
              if (this === bubbles.node()) {
                pulse();
              }
            });
        };

        // Démarrer l'animation après un délai pour laisser la page charger
        setTimeout(pulse, 2000);

        // Add zoom capability
        const zoom = d3.zoom<SVGSVGElement, unknown>()
          .scaleExtent([1, 8])
          .on('zoom', (event) => {
            g.attr('transform', event.transform);
          });

        svg.call(zoom);
      });

    // Cleanup function to stop animations when component unmounts
    return () => {
      if (svgRef.current) {
        d3.select(svgRef.current).selectAll('*').interrupt();
      }
    };
  }, []);

  return (
    <>
      <div
        ref={containerRef}
        className={`relative w-full ${isFullscreen ? 'bg-white dark:bg-gray-900' : ''}`}
      >
        {/* Fullscreen toggle button */}
        <button
          onClick={toggleFullscreen}
          className="absolute top-4 right-4 z-10 bg-white/90 dark:bg-gray-800/90 hover:bg-white dark:hover:bg-gray-800 rounded-lg p-2 shadow-lg transition-all duration-200 border border-gray-200 dark:border-gray-700"
          title={isFullscreen ? 'Quitter le plein écran' : 'Plein écran'}
        >
          {isFullscreen ? (
            <svg className="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          ) : (
            <svg className="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
          )}
        </button>

        <svg
          ref={svgRef}
          className={`w-full h-auto ${isFullscreen ? 'h-screen' : ''}`}
          style={isFullscreen ? {} : { maxHeight: '600px' }}
        />

        {/* Legend and Stats - Only show on map when in fullscreen */}
        {isFullscreen && (
          <>
            {/* Legend */}
            <div className="absolute bottom-4 left-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg shadow-lg p-4">
              <h4 className="text-sm font-bold text-gray-900 dark:text-white mb-2">Légende</h4>
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-purple-500"></div>
                  <span className="text-xs text-gray-600 dark:text-gray-300">Églises ICC</span>
                </div>
                <div className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                  Taille = nombre de membres
                </div>
              </div>
            </div>

            {/* Stats */}
            <div className="absolute top-20 right-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-lg shadow-lg p-4">
              <h4 className="text-xs font-bold text-gray-900 dark:text-white mb-3 uppercase tracking-wide">Statistiques</h4>
              <div className="space-y-3">
                <div>
                  <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">{stats.totalChurches}</div>
                  <div className="text-xs text-gray-600 dark:text-gray-300">Églises</div>
                </div>
                <div>
                  <div className="text-2xl font-bold text-green-600 dark:text-green-400">{stats.totalCountries}</div>
                  <div className="text-xs text-gray-600 dark:text-gray-300">Pays</div>
                </div>
                <div>
                  <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">{stats.totalMembers.toLocaleString()}</div>
                  <div className="text-xs text-gray-600 dark:text-gray-300">Membres</div>
                </div>
                <div className="pt-2 border-t border-gray-200 dark:border-gray-700">
                  <div className="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <div className="flex justify-between">
                      <span>Europe:</span>
                      <span className="font-semibold">{stats.continents.europe}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Afrique:</span>
                      <span className="font-semibold">{stats.continents.africa}</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Amériques:</span>
                      <span className="font-semibold">{stats.continents.northAmerica + stats.continents.southAmerica}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </>
        )}
      </div>

      {/* Legend and Stats below map - Only show when NOT in fullscreen */}
      {!isFullscreen && (
        <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Legend */}
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Légende</h4>
            <div className="space-y-3">
              <div className="flex items-center gap-3">
                <div className="w-4 h-4 rounded-full bg-purple-500"></div>
                <span className="text-sm text-gray-600 dark:text-gray-300">Églises ICC</span>
              </div>
              <div className="text-sm text-gray-500 dark:text-gray-400">
                La taille des cercles représente le nombre de membres
              </div>
            </div>
          </div>

          {/* Stats */}
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Statistiques</h4>
            <div className="grid grid-cols-3 gap-4 mb-4">
              <div className="text-center">
                <div className="text-3xl font-bold text-purple-600 dark:text-purple-400">{stats.totalChurches}</div>
                <div className="text-xs text-gray-600 dark:text-gray-300 mt-1">Églises</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-green-600 dark:text-green-400">{stats.totalCountries}</div>
                <div className="text-xs text-gray-600 dark:text-gray-300 mt-1">Pays</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-purple-600 dark:text-purple-400">{stats.totalMembers.toLocaleString()}</div>
                <div className="text-xs text-gray-600 dark:text-gray-300 mt-1">Membres</div>
              </div>
            </div>
            <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
              <div className="text-sm text-gray-500 dark:text-gray-400 space-y-2">
                <div className="flex justify-between">
                  <span>Europe:</span>
                  <span className="font-semibold text-gray-700 dark:text-gray-300">{stats.continents.europe}</span>
                </div>
                <div className="flex justify-between">
                  <span>Afrique:</span>
                  <span className="font-semibold text-gray-700 dark:text-gray-300">{stats.continents.africa}</span>
                </div>
                <div className="flex justify-between">
                  <span>Amériques:</span>
                  <span className="font-semibold text-gray-700 dark:text-gray-300">{stats.continents.northAmerica + stats.continents.southAmerica}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Render tooltip using Portal to escape overflow-hidden */}
      {isMounted && createPortal(
        <div
          ref={tooltipRef}
          className="pointer-events-none bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-4 border-2 border-purple-500 transition-opacity duration-200"
          style={{
            opacity: 0,
            zIndex: 9999,
            display: 'none',
            minWidth: '200px',
            position: isFullscreen ? 'absolute' : 'fixed',
            top: 0,
            left: 0
          }}
        />,
        isFullscreen && containerRef.current ? containerRef.current : document.body
      )}
    </>
  );
};

export default WorldMap;
