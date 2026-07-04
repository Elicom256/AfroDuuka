import { HeroSection } from './home/HeroSection';
import { ProblemSolutionSection } from './home/ProblemSolutionSection';
import { FeaturesSection } from './home/FeaturesSection';
import { StatsSection } from './home/StatsSection';
import { TestimonialsSection } from './home/TestimonialsSection';
import { CtaSection } from './home/CtaSection';
import { FaqSection } from './home/FaqSection';
import { AboutSection } from './home/AboutSection';

export const Home: React.FC = () => {
  return (
    <>
      <HeroSection />
      <ProblemSolutionSection />
      <FeaturesSection />
      <StatsSection />
      <TestimonialsSection />
      <CtaSection />
      <FaqSection />
      <AboutSection />
    </>
  );
};
