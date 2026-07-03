import { HeroSection } from './home/HeroSection';
import { ProblemSolutionSection } from './home/ProblemSolutionSection';
import { FeaturesSection } from './home/FeaturesSection';
import { StatsSection } from './home/StatsSection';
import { TestimonialsSection } from './home/TestimonialsSection';
import { PricingSection } from '@/app/components/PricingSection';
import { CtaSection } from './home/CtaSection';
import { FaqSection } from './home/FaqSection';
import { AboutSection } from './home/AboutSection';

export const Home: React.FC = () => {
  return (
    <div className='container mx-auto px-4 py-10 sm:py-14'>
      <HeroSection />
      <ProblemSolutionSection />
      <FeaturesSection />
      <StatsSection />
      <TestimonialsSection />
      <PricingSection />
      <CtaSection />
      <FaqSection />
      <AboutSection />
    </div>
  );
};
