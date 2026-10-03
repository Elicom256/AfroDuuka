import { QueryErrorState } from '@/app/components/QueryErrorState';

type ErrorProps = {
  title: string;
  onRetry?: () => unknown;
  retrying?: boolean;
};

export const Error = ({ title, onRetry, retrying }: ErrorProps) => (
  <QueryErrorState
    title={title}
    description='The report request failed. Check the connection and try again.'
    onRetry={onRetry}
    retrying={retrying}
  />
);
