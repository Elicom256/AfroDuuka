import React from 'react';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
  PaginationEllipsis,
} from '@/components/ui/pagination';

interface PaginationProps {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  siblingCount?: number;
  boundaryCount?: number;
  showLabel?: boolean;
}

const range = (start: number, end: number): number[] => {
  const length = end - start + 1;
  return Array.from({ length }, (_, i) => start + i);
};

export const PaginationComponent: React.FC<PaginationProps> = ({
  currentPage,
  totalPages,
  onPageChange,
  siblingCount = 1,
  boundaryCount = 1,
  showLabel = false,
}) => {
  if (totalPages <= 1) return null;

  const totalPageNumbers = siblingCount * 2 + boundaryCount * 2 + 3; // siblings + boundaries + first + last + current

  if (totalPages <= totalPageNumbers) {
    return (
      <Pagination>
        <PaginationContent>
          <PaginationItem>
            <PaginationPrevious
              onClick={() => currentPage > 1 && onPageChange(currentPage - 1)}
              text={showLabel ? 'Previous' : ''}
            />
          </PaginationItem>
          {range(1, totalPages).map((page) => (
            <PaginationItem key={page}>
              <PaginationLink isActive={page === currentPage} onClick={() => onPageChange(page)}>
                {page}
              </PaginationLink>
            </PaginationItem>
          ))}
          <PaginationItem>
            <PaginationNext
              onClick={() => currentPage < totalPages && onPageChange(currentPage + 1)}
              text={showLabel ? 'Next' : ''}
            />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    );
  }

  const leftSiblingStart = Math.max(currentPage - siblingCount, boundaryCount + 2);
  const leftSiblingEnd = Math.min(currentPage + siblingCount, totalPages - boundaryCount - 1);

  const showLeftEllipsis = leftSiblingStart > boundaryCount + 2;
  const showRightEllipsis = leftSiblingEnd < totalPages - boundaryCount - 1;

  const pageNumbers: (number | 'ellipsis')[] = [];

  // first pages
  for (let i = 1; i <= boundaryCount; i++) {
    pageNumbers.push(i);
  }

  if (showLeftEllipsis) {
    pageNumbers.push('ellipsis');
  } else {
    for (let i = boundaryCount + 1; i < leftSiblingStart; i++) {
      pageNumbers.push(i);
    }
  }

  // middle pages
  for (let i = leftSiblingStart; i <= leftSiblingEnd; i++) {
    pageNumbers.push(i);
  }

  if (showRightEllipsis) {
    pageNumbers.push('ellipsis');
  } else {
    for (let i = leftSiblingEnd + 1; i <= totalPages - boundaryCount; i++) {
      pageNumbers.push(i);
    }
  }

  // last pages
  for (let i = totalPages - boundaryCount + 1; i <= totalPages; i++) {
    pageNumbers.push(i);
  }

  return (
    <Pagination>
      <PaginationContent>
        <PaginationItem>
          <PaginationPrevious
            onClick={() => currentPage > 1 && onPageChange(currentPage - 1)}
            text={showLabel ? 'Previous' : ''}
          />
        </PaginationItem>
        {pageNumbers.map((page, idx) =>
          page === 'ellipsis' ? (
            <PaginationItem key={`ellipsis-${idx}`}>
              <PaginationEllipsis />
            </PaginationItem>
          ) : (
            <PaginationItem key={page}>
              <PaginationLink isActive={page === currentPage} onClick={() => onPageChange(page)}>
                {page}
              </PaginationLink>
            </PaginationItem>
          ),
        )}
        <PaginationItem>
          <PaginationNext
            onClick={() => currentPage < totalPages && onPageChange(currentPage + 1)}
            text={showLabel ? 'Next' : ''}
          />
        </PaginationItem>
      </PaginationContent>
    </Pagination>
  );
};
