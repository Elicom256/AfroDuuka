import type { ReactNode } from 'react';
import { FeatureUnavailable } from '@/app/components/FeatureUnavailable';
import { useFeatureSettings } from '@/app/hooks/useFeatureSettings';
import { FEATURE_LABELS, type FeatureKey } from '@/app/lib/featureLabels';
import { PageLoadingState } from '@/utils/PageLoadingState';

type RequireFeatureProps = {
  feature: FeatureKey;
  children: ReactNode;
};

/**
 * Stops a page from rendering — and stops its queries from firing — when the
 * feature behind it is switched off for the business.
 *
 * The api answers a disabled feature with a 403 (`feature_disabled`), which the
 * request layer would surface as a toast and leave the page itself blank. This
 * guard renders an explicit, human state instead, so a feature that is off looks
 * like a feature that is off rather than a broken page.
 *
 * The source of truth is the same `useFeatureSettings` the sidebars use, so a
 * feature hidden from the sidebar is also refused here — the two can never
 * disagree.
 */
export const RequireFeature = ({ feature, children }: RequireFeatureProps) => {
  const features = useFeatureSettings();

  if (features.loading) {
    return <PageLoadingState />;
  }

  if (!features[feature]) {
    return <FeatureUnavailable feature={FEATURE_LABELS[feature]} />;
  }

  return <>{children}</>;
};
